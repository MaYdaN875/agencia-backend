import { ChromaClient } from "chromadb";
import { pipeline } from "@xenova/transformers";
import { getAllHotels } from "../models/Hotel.js";
import { connection } from "../database/config.js";

const chromaUrl = process.env.CHROMA_URL || "http://localhost:8000";
const client = new ChromaClient({ path: chromaUrl });

let collection = null;
let extractor = null;

/**
 * Retorna la instancia única del modelo de embeddings local.
 * Descarga y carga el modelo multilingual-e5-small de HuggingFace en el primer llamado.
 */
async function getExtractor() {
  if (!extractor) {
    console.log("⏳ Cargando modelo de embeddings local (Xenova/multilingual-e5-small)...");
    extractor = await pipeline("feature-extraction", "Xenova/multilingual-e5-small");
    console.log("✅ Modelo de embeddings local cargado correctamente.");
  }
  return extractor;
}

/**
 * Genera un embedding vectorial de 384 dimensiones para un texto dado.
 * @param {string} text - El texto a convertir en vector.
 * @returns {Promise<number[]>} El vector como un array de números.
 */
export async function getEmbedding(text) {
  try {
    const model = await getExtractor();
    const output = await model(text, { pooling: "mean", normalize: true });
    return Array.from(output.data);
  } catch (error) {
    console.error("❌ Error generando embedding:", error);
    throw error;
  }
}

/**
 * Espera a que la conexión de MySQL esté completamente lista.
 */
async function waitForMysql() {
  let attempts = 0;
  while ((typeof connection === "function" || !connection.execute) && attempts < 30) {
    console.log("⏳ Esperando a que la conexión a MySQL esté lista para sincronizar...");
    await new Promise((resolve) => setTimeout(resolve, 1000));
    attempts++;
  }
  if (typeof connection === "function" || !connection.execute) {
    throw new Error("No se pudo establecer la conexión a MySQL tras 30 segundos.");
  }
  console.log("📌 Conexión a MySQL detectada en el inicializador vectorial.");
}

/**
 * Sincroniza todos los hoteles de la base de datos MySQL con ChromaDB.
 */
export async function syncHotelsToVectorDb() {
  if (!collection) {
    console.error("❌ No se puede sincronizar: ChromaDB no está inicializado.");
    return;
  }

  try {
    await waitForMysql();
    console.log("🔄 Iniciando sincronización de hoteles de MySQL a ChromaDB...");
    const hotels = await getAllHotels();

    if (!hotels || hotels.length === 0) {
      console.log("ℹ️ No hay hoteles en MySQL para sincronizar.");
      return;
    }

    const ids = [];
    const documents = [];
    const embeddings = [];
    const metadatas = [];

    for (const hotel of hotels) {
      const id = String(hotel.id);
      ids.push(id);

      // Creamos el documento que representará semánticamente al hotel en lenguaje natural con el prefijo "passage: " requerido por E5
      const starsText = hotel.stars ? `, hotel de ${hotel.stars} estrellas` : '';
      const textToEmbed = `passage: ${hotel.name}${starsText} en ${hotel.location}. ${hotel.description || "Sin descripción disponible"}. Ubicado en ${hotel.address || ""}.`;
      documents.push(textToEmbed);

      console.log(`   -> Generando embedding para hotel [ID: ${hotel.id}] "${hotel.name}"...`);
      const embedding = await getEmbedding(textToEmbed);
      embeddings.push(embedding);

      metadatas.push({
        id: hotel.id,
        name: hotel.name,
        location: hotel.location,
        stars: hotel.stars || 3
      });
    }

    // Insertar o actualizar en ChromaDB
    await collection.upsert({
      ids,
      embeddings,
      metadatas,
      documents
    });

    console.log(`✅ Sincronización completada. ${hotels.length} hoteles indexados en ChromaDB.`);
  } catch (error) {
    console.error("❌ Error sincronizando hoteles con ChromaDB:", error);
  }
}

/**
 * Inicializa ChromaDB, obtiene/crea la colección y sincroniza los datos.
 */
export async function initVectorDb() {
  try {
    console.log(`🔌 Conectando a ChromaDB en: ${chromaUrl}`);
    collection = await client.getOrCreateCollection({
      name: "hotels_collection",
      metadata: { "hnsw:space": "cosine" } // Usamos similitud coseno
    });
    console.log("📌 Colección 'hotels_collection' en ChromaDB lista.");

    // Sincronizar en segundo plano sin bloquear el arranque del servidor
    syncHotelsToVectorDb();
  } catch (error) {
    console.error("❌ Error inicializando ChromaDB:", error.message);
    console.log("⏳ Reintentando conectar a ChromaDB en 5s...");
    setTimeout(initVectorDb, 5000);
  }
}

/**
 * Busca hoteles semánticamente a partir de una consulta de texto.
 * @param {string} queryText - Consulta en lenguaje natural.
 * @param {number} limit - Número máximo de resultados.
 */
export async function searchHotelsSemantically(queryText, limit = 5) {
  if (!collection) {
    throw new Error("La base de datos vectorial ChromaDB no está lista.");
  }

  // Los modelos de la familia E5 requieren el prefijo "query: " para búsquedas semánticas
  const queryWithPrefix = `query: ${queryText}`;
  console.log(`🔎 Realizando búsqueda semántica para: "${queryText}" (con prefijo E5)`);
  const queryEmbedding = await getEmbedding(queryWithPrefix);

  const results = await collection.query({
    queryEmbeddings: [queryEmbedding],
    nResults: limit
  });

  if (!results || !results.ids || results.ids.length === 0 || results.ids[0].length === 0) {
    return [];
  }

  const mapped = [];
  const ids = results.ids[0];
  const distances = results.distances[0];
  const metadatas = results.metadatas[0];
  const documents = results.documents[0];

  for (let i = 0; i < ids.length; i++) {
    // La distancia de similitud coseno oscila entre 0 y 2 en Chroma.
    // 0 significa idéntico, 1 ortogonal, 2 opuesto.
    // Convertimos la distancia en un porcentaje de similitud orientativo
    const distance = distances[i];
    const similarity = Math.max(0, (1 - distance) * 100).toFixed(1);

    mapped.push({
      id: Number(ids[i]),
      name: metadatas[i].name,
      location: metadatas[i].location,
      stars: metadatas[i].stars,
      // Removemos el prefijo "passage: " para retornar un texto limpio al cliente
      document: documents[i].replace(/^passage:\s*/, ''),
      similarity: `${similarity}%`
    });
  }

  return mapped;
}
