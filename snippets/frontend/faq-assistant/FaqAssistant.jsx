/*
 * Asistente FAQ como árbol de decisión que corre 100% en el cliente.
 * Decisión: los nodos son datos (mensaje + opciones o nodo final), así el
 * flujo se edita sin tocar la lógica; no hay backend ni IA, y cada respuesta
 * final lleva al formulario de agendamiento con un Link de Inertia.
 */
import React, { useState } from 'react';
import { Link } from '@inertiajs/react';

export default function FaqAssistant() {
  const [abierto, setAbierto] = useState(false);
  const [pasoActual, setPasoActual] = useState('inicio');

  // Árbol recortado a 2 ramas de ejemplo
  const flujoChat = {
    inicio: {
      mensaje: "¡Hola! Soy el asistente virtual de Acme. ¿En qué te puedo orientar hoy?",
      opciones: [
        { texto: "Dudas sobre requisitos", siguiente: "requisitos" },
        { texto: "Casos especiales", siguiente: "casos" }
        // ...
      ]
    },
    requisitos: {
      mensaje: "Selecciona tu duda sobre los requisitos:",
      opciones: [
        { texto: "¿Cumplo con los requisitos?", siguiente: "ans_requisito" },
        // ...
        { texto: "Volver al inicio", siguiente: "inicio" }
      ]
    },
    casos: {
      mensaje: "Revisemos tu caso particular:",
      opciones: [
        { texto: "Mi caso es distinto", siguiente: "ans_caso" },
        // ...
        { texto: "Volver al inicio", siguiente: "inicio" }
      ]
    },
    // --- NODOS DE RESPUESTA FINAL ---
    ans_requisito: {
      mensaje: "Los requisitos dependen de tu situación. En una evaluación los revisamos contigo. ¿Deseas agendar?",
      final: true
    },
    ans_caso: {
      mensaje: "Cada caso se revisa por separado. ¿Agendamos una evaluación para conocer tu situación?",
      final: true
    }
  };

  const nodoActual = flujoChat[pasoActual];

  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end">
      {abierto && (
        <div className="w-80 sm:w-96 bg-white rounded-2xl shadow-2xl mb-4 overflow-hidden border border-gray-100 flex flex-col">
          {/* ... header con botón de cierre */}

          <div className="p-4 bg-brand-cloud min-h-[200px] max-h-[400px] overflow-y-auto flex flex-col gap-3">
            <div className="bg-white border border-gray-100 p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-gray-700 leading-relaxed inline-block max-w-[90%]">
              {nodoActual.mensaje}
            </div>
          </div>

          <div className="p-4 bg-white border-t border-gray-100 flex flex-col gap-2">
            {!nodoActual.final ? (
              // Botones de navegación del árbol
              nodoActual.opciones.map((opcion, index) => (
                <button
                  key={index}
                  onClick={() => setPasoActual(opcion.siguiente)}
                  className="text-left px-4 py-2 text-sm bg-brand-amalfi/5 hover:bg-brand-amalfi/10 text-brand-ink border border-brand-amalfi/20 rounded-xl transition-colors"
                >
                  {opcion.texto}
                </button>
              ))
            ) : (
              // Botones finales (Redirección a /agendar o reiniciar)
              <>
                <Link
                  href="/agendar"
                  onClick={() => setAbierto(false)}
                  className="text-center px-4 py-3 text-sm font-bold bg-brand-amalfi hover:bg-brand-amalfi/90 text-white rounded-xl shadow-md transition-all duration-200"
                >
                  Agendar Evaluación
                </Link>
                <button
                  onClick={() => setPasoActual('inicio')}
                  className="text-center px-4 py-2 text-sm text-gray-500 hover:text-gray-800 transition-colors"
                >
                  Hacer otra consulta
                </button>
              </>
            )}
          </div>
        </div>
      )}

      <button
        onClick={() => {
          setAbierto(!abierto);
          if (!abierto) setPasoActual('inicio'); // Reinicia el chat al abrirlo
        }}
        className="relative w-16 h-16 md:w-20 md:h-20 bg-brand-amalfi hover:bg-brand-ink text-white rounded-full shadow-lg flex items-center justify-center"
        aria-label="Abrir asistente virtual"
      >
        {/* ... ícono SVG */}
      </button>
    </div>
  );
}
