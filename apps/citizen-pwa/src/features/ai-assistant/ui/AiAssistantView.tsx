import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import type { AiChatMessage, AiSuggestedAction } from '../types';
import { sendChatMessageStream, sendVoiceMessage } from '../api/aiClient';
import { useVoiceRecorder } from '../hooks/useVoiceRecorder';
import { ChatHeader } from '../components/ChatHeader';
import { ChatInputBar } from '../components/ChatInputBar';
import { ChatMessageBubble } from '../components/ChatMessageBubble';

const INITIAL_MESSAGE: AiChatMessage = {
  id: 'welcome_1',
  role: 'assistant',
  content: 'سلام! من دستیار هوشمند پیشخوان خدمات شهروندی هستم. چطور می‌توانم در امور اداری، مدارک مورد نیاز یا نوبت‌دهی به شما کمک کنم؟',
  created_at: new Date().toISOString(),
  suggested_actions: [
    { type: 'open_service', label: 'مدارک کارت هوشمند ملی', payload: { service_id: 'id-national-card' } },
    { type: 'open_office', label: 'نزدیک‌ترین دفاتر پیشخوان', payload: {} },
  ],
};

export const AiAssistantView: React.FC = () => {
  const navigate = useNavigate();
  const [messages, setMessages] = useState<AiChatMessage[]>([INITIAL_MESSAGE]);
  const [inputValue, setInputValue] = useState('');
  const [conversationId, setConversationId] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  const { isSupported, isRecording, duration, startRecording, stopRecording } = useVoiceRecorder();
  const messagesEndRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView?.({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async () => {
    const text = inputValue.trim();
    if (!text || isProcessing) return;

    setInputValue('');
    setIsProcessing(true);

    const streamMsgId = `asst_${Date.now()}`;
    setMessages((prev) => [
      ...prev,
      { id: `usr_${Date.now()}`, role: 'user', content: text, created_at: new Date().toISOString() },
      { id: streamMsgId, role: 'assistant', content: '', isStreaming: true, created_at: new Date().toISOString() },
    ]);

    let accumulatedContent = '';

    await sendChatMessageStream(text, conversationId, {}, {
      onMeta: (meta) => {
        if (meta.conversation_id) setConversationId(meta.conversation_id);
      },
      onToken: (token) => {
        accumulatedContent += token;
        setMessages((prev) => prev.map((m) => m.id === streamMsgId ? { ...m, content: accumulatedContent } : m));
      },
      onDone: (doneData) => {
        if (doneData.conversation_id) setConversationId(doneData.conversation_id);
        setMessages((prev) => prev.map((m) => {
          if (m.id !== streamMsgId) return m;
          const updated: AiChatMessage = {
            ...m,
            content: doneData.reply || accumulatedContent,
            isStreaming: false,
          };
          if (doneData.citations) updated.citations = doneData.citations;
          if (doneData.suggested_actions) updated.suggested_actions = doneData.suggested_actions;
          return updated;
        }));
        setIsProcessing(false);
      },
      onError: (err) => {
        setMessages((prev) => prev.map((m) => m.id === streamMsgId ? {
          ...m,
          content: err.message || 'پاسخ موقتاً با خطا مواجه شد.',
          isStreaming: false,
        } : m));
        setIsProcessing(false);
      },
    });
  };

  const handleVoiceToggle = async () => {
    if (isRecording) {
      try {
        setIsProcessing(true);
        const blob = await stopRecording();
        const res = await sendVoiceMessage(blob, duration, conversationId);
        if (res.conversation_id) setConversationId(res.conversation_id);

        const asstMsg: AiChatMessage = {
          id: `asst_${Date.now()}`,
          role: 'assistant',
          content: res.reply,
          created_at: new Date().toISOString(),
        };
        if (res.citations) asstMsg.citations = res.citations;
        if (res.suggested_actions) asstMsg.suggested_actions = res.suggested_actions;

        setMessages((prev) => [
          ...prev,
          { id: `usr_${Date.now()}`, role: 'user', content: res.transcript, created_at: new Date().toISOString() },
          asstMsg,
        ]);
      } catch (err: any) {
        alert(err.message || 'خطا در ارسال صوت');
      } finally {
        setIsProcessing(false);
      }
    } else {
      await startRecording();
    }
  };

  const handleActionClick = (action: AiSuggestedAction) => {
    if (action.type === 'open_office') navigate('/map');
    else if (action.type === 'open_service' && action.payload?.service_id) navigate(`/services/${action.payload.service_id}`);
    else if (action.type === 'track_case') navigate('/cases');
  };

  const handleNewConversation = () => {
    setConversationId(null);
    setMessages([
      {
        id: `new_${Date.now()}`,
        role: 'assistant',
        content: 'مکالمه جدید آغاز شد. چه سوالی دارید؟',
        created_at: new Date().toISOString(),
      },
    ]);
  };

  return (
    <div data-testid="ai-assistant-container" className="flex flex-col h-[calc(100vh-4rem)] max-w-2xl mx-auto p-3 sm:p-4">
      <ChatHeader onNewConversation={handleNewConversation} />

      <main className="flex-1 overflow-y-auto px-1 py-2 space-y-2">
        {messages.map((msg) => (
          <ChatMessageBubble key={msg.id} message={msg} onActionClick={handleActionClick} />
        ))}
        <div ref={messagesEndRef} />
      </main>

      <ChatInputBar
        inputValue={inputValue}
        onInputChange={setInputValue}
        onSend={handleSend}
        isProcessing={isProcessing}
        isSupported={isSupported}
        isRecording={isRecording}
        duration={duration}
        onVoiceToggle={handleVoiceToggle}
      />
    </div>
  );
};
