
import React, { useState, useRef, useEffect } from 'react';
import { GoogleGenAI } from "@google/genai";

const ChatBot: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<{ role: 'user' | 'bot'; text: string }[]>([
    { role: 'bot', text: '¡Hola! Soy el asistente virtual de Mundo Jácome\'s. ¿En qué puedo ayudarte hoy?' }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages]);

  const handleSend = async () => {
    if (!input.trim() || loading) return;

    const userMsg = input.trim();
    setMessages(prev => [...prev, { role: 'user', text: userMsg }]);
    setInput('');
    setLoading(true);

    try {
      // Use process.env.API_KEY as provided in the environment
      const ai = new GoogleGenAI({ apiKey: (process.env.API_KEY as string) });
      const response = await ai.models.generateContent({
        model: 'gemini-3-flash-preview',
        contents: userMsg,
        config: {
          systemInstruction: `Eres el asistente virtual de la Clínica Veterinaria Mundo Jácome's en Táriba, Táchira, Venezuela. 
          Tu tono debe ser amable, profesional y empático. 
          Ofrece información sobre: consultas, cirugía, exportación internacional (INSAI), laboratorio y estética. 
          IMPORTANTE: Si el usuario pregunta por una emergencia médica, dile que acuda inmediatamente a la clínica o llame al 0412-4506665. 
          No des diagnósticos médicos definitivos, siempre sugiere una consulta presencial.`,
        }
      });

      const botText = response.text || 'Lo siento, no pude procesar tu solicitud en este momento.';
      setMessages(prev => [...prev, { role: 'bot', text: botText }]);
    } catch (error) {
      console.error(error);
      setMessages(prev => [...prev, { role: 'bot', text: 'Error de conexión. Por favor intenta más tarde.' }]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed bottom-6 right-6 z-[60]">
      {isOpen ? (
        <div className="bg-white w-80 sm:w-96 h-[500px] rounded-[32px] shadow-2xl flex flex-col border border-vibrant-dark/10 overflow-hidden animate-fade-in-up">
          {/* Header */}
          <div className="bg-vibrant-main p-4 flex justify-between items-center text-white">
            <div className="flex items-center space-x-3">
              <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold">MJ</div>
              <div>
                <p className="font-bold leading-none">Asistente MJ</p>
                <p className="text-[10px] opacity-80 uppercase tracking-widest font-bold">En línea</p>
              </div>
            </div>
            <button onClick={() => setIsOpen(false)} className="p-2 hover:bg-white/10 rounded-full transition-colors">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          {/* Messages */}
          <div ref={scrollRef} className="flex-grow overflow-y-auto p-4 space-y-4 bg-vibrant-light">
            {messages.map((m, i) => (
              <div key={i} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div className={`max-w-[85%] p-3 rounded-2xl text-sm ${
                  m.role === 'user' 
                  ? 'bg-vibrant-main text-white rounded-tr-none' 
                  : 'bg-white text-vibrant-dark shadow-sm border border-vibrant-dark/5 rounded-tl-none'
                }`}>
                  {m.text}
                </div>
              </div>
            ))}
            {loading && (
              <div className="flex justify-start">
                <div className="bg-white p-3 rounded-2xl rounded-tl-none animate-pulse text-vibrant-dark/40 text-xs">
                  Escribiendo...
                </div>
              </div>
            )}
          </div>

          {/* Input */}
          <div className="p-4 bg-white border-t border-vibrant-dark/5 flex space-x-2">
            <input 
              type="text" 
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSend()}
              placeholder="Escribe tu mensaje..."
              className="flex-grow bg-vibrant-light border-0 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-vibrant-main outline-none"
            />
            <button 
              onClick={handleSend}
              disabled={loading}
              className="bg-vibrant-main text-white p-2 rounded-xl hover:bg-vibrant-dark transition-colors disabled:opacity-50"
            >
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg>
            </button>
          </div>
        </div>
      ) : (
        <button 
          onClick={() => setIsOpen(true)}
          className="bg-vibrant-main text-white w-16 h-16 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all group relative"
        >
          <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
          <span className="absolute -top-1 -right-1 bg-red-500 w-4 h-4 rounded-full border-2 border-white"></span>
          <div className="absolute right-full mr-4 bg-white px-4 py-2 rounded-xl text-vibrant-dark font-bold text-sm shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
            ¿Necesitas ayuda?
          </div>
        </button>
      )}
    </div>
  );
};

export default ChatBot;
