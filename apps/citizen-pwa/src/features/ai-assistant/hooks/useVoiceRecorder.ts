import { useState, useRef, useCallback } from 'react';

export function useVoiceRecorder() {
  const isSupported =
    typeof window !== 'undefined' &&
    typeof window.MediaRecorder !== 'undefined' &&
    !!navigator?.mediaDevices?.getUserMedia;

  const [isRecording, setIsRecording] = useState(false);
  const [duration, setDuration] = useState(0);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const timerRef = useRef<number | null>(null);
  const chunksRef = useRef<Blob[]>([]);

  const startRecording = useCallback(async (): Promise<void> => {
    if (!isSupported) {
      throw new Error('مرورگر شما از ضبط صدا پشتیبانی نمی‌کند.');
    }

    chunksRef.current = [];
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
    mediaRecorderRef.current = recorder;

    recorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) {
        chunksRef.current.push(e.data);
      }
    };

    recorder.start(250);
    setIsRecording(true);
    setDuration(0);

    const startTime = Date.now();
    timerRef.current = window.setInterval(() => {
      const elapsed = Math.floor((Date.now() - startTime) / 1000);
      setDuration(elapsed);
      if (elapsed >= 30) {
        stopRecording();
      }
    }, 500);
  }, [isSupported]);

  const stopRecording = useCallback((): Promise<Blob> => {
    return new Promise((resolve) => {
      if (timerRef.current) {
        clearInterval(timerRef.current);
        timerRef.current = null;
      }

      const recorder = mediaRecorderRef.current;
      if (!recorder || recorder.state === 'inactive') {
        setIsRecording(false);
        resolve(new Blob(chunksRef.current, { type: 'audio/webm' }));
        return;
      }

      recorder.onstop = () => {
        setIsRecording(false);
        const audioBlob = new Blob(chunksRef.current, { type: 'audio/webm' });
        // Stop all tracks to release mic
        recorder.stream.getTracks().forEach((track) => track.stop());
        resolve(audioBlob);
      };

      recorder.stop();
    });
  }, []);

  return {
    isSupported,
    isRecording,
    duration,
    startRecording,
    stopRecording,
  };
}
