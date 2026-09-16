import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import fs from 'fs';
import path from 'path';
import { AiAssistantView } from '../ui/AiAssistantView';
import * as aiClient from '../api/aiClient';

const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockedNavigate,
  };
});

describe('Citizen AI Assistant Slice (§4.1, §8.1.5, HC-5, TASK-115, TASK-115-T)', () => {
  const originalMediaRecorder = window.MediaRecorder;
  const originalMediaDevices = navigator.mediaDevices;

  beforeEach(() => {
    vi.clearAllMocks();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  afterEach(() => {
    window.MediaRecorder = originalMediaRecorder;
    Object.defineProperty(navigator, 'mediaDevices', {
      value: originalMediaDevices,
      configurable: true,
      writable: true,
    });
  });

  it('renders initial assistant view with welcome message, suggested actions, and input bar', () => {
    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    expect(screen.getByTestId('ai-assistant-container')).toBeInTheDocument();
    expect(screen.getByText('دستیار هوشمند پیشخوان')).toBeInTheDocument();
    expect(
      screen.getByText(/سلام! من دستیار هوشمند پیشخوان خدمات شهروندی هستم/)
    ).toBeInTheDocument();
    expect(screen.getByText('مدارک کارت هوشمند ملی')).toBeInTheDocument();
    expect(screen.getByText('نزدیک‌ترین دفاتر پیشخوان')).toBeInTheDocument();
    expect(screen.getByTestId('ai-chat-input')).toBeInTheDocument();
    expect(screen.getByTestId('ai-send-btn')).toBeInTheDocument();
  });

  it('Fallback §8.1.5: hides microphone button and shows voice-fallback message when MediaRecorder is unavailable', () => {
    // Simulate browser without MediaRecorder support
    // @ts-expect-error test override
    delete window.MediaRecorder;

    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    expect(screen.queryByTestId('voice-record-btn')).not.toBeInTheDocument();
    const fallbackNotice = screen.getByTestId('voice-fallback');
    expect(fallbackNotice).toBeInTheDocument();
    expect(fallbackNotice).toHaveTextContent(
      'ضبط صدا در مرورگر شما پشتیبانی نمی‌شود، لطفاً پیام خود را بنویسید.'
    );
  });

  it('renders voice recording button and toggles recording when MediaRecorder is supported', async () => {
    const mockStart = vi.fn();
    const mockStop = vi.fn();
    const mockStream = { getTracks: () => [{ stop: vi.fn() }] };

    class MockMediaRecorder {
      state = 'inactive';
      ondataavailable: ((e: any) => void) | null = null;
      onstop: (() => void) | null = null;
      start = mockStart;
      stop = () => {
        this.state = 'inactive';
        if (this.ondataavailable) {
          this.ondataavailable({ data: new Blob(['fake_audio'], { type: 'audio/webm' }) });
        }
        if (this.onstop) this.onstop();
        mockStop();
      };
      constructor() {
        this.state = 'recording';
      }
    }

    // @ts-expect-error test override
    window.MediaRecorder = MockMediaRecorder;
    Object.defineProperty(navigator, 'mediaDevices', {
      value: {
        getUserMedia: vi.fn().mockResolvedValue(mockStream),
      },
      configurable: true,
      writable: true,
    });

    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    const voiceBtn = screen.getByTestId('voice-record-btn');
    expect(voiceBtn).toBeInTheDocument();
    expect(screen.queryByTestId('voice-fallback')).not.toBeInTheDocument();

    fireEvent.click(voiceBtn);
    await waitFor(() => {
      expect(navigator.mediaDevices.getUserMedia).toHaveBeenCalledWith({ audio: true });
    });
  });

  it('streams chat message tokens progressively on send', async () => {
    const streamSpy = vi.spyOn(aiClient, 'sendChatMessageStream').mockImplementation(
      async (_prompt, _convId, _ctx, callbacks) => {
        callbacks?.onMeta?.({ conversation_id: 'conv_123', intent: 'inquiry', confidence: 0.95 });
        callbacks?.onToken?.('برای تعویض ');
        callbacks?.onToken?.('کارت هوشمند ملی ');
        callbacks?.onToken?.('به شناسنامه نیاز دارید.');
        callbacks?.onDone?.({
          conversation_id: 'conv_123',
          message_id: 'msg_123',
          reply: 'برای تعویض کارت هوشمند ملی به شناسنامه نیاز دارید.',
          citations: [{ type: 'service', id: 'id-national-card', title: 'کارت هوشمند ملی' }],
          suggested_actions: [{ type: 'open_service', label: 'اطلاعات کارت هوشمند ملی', payload: { service_id: 'id-national-card' } }],
        });
      }
    );

    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    const input = screen.getByTestId('ai-chat-input');
    const sendBtn = screen.getByTestId('ai-send-btn');

    fireEvent.change(input, { target: { value: 'مدارک کارت هوشمند ملی چیست؟' } });
    fireEvent.click(sendBtn);

    expect(await screen.findByText('مدارک کارت هوشمند ملی چیست؟')).toBeInTheDocument();
    expect(
      await screen.findByText(/برای تعویض کارت هوشمند ملی به شناسنامه نیاز دارید/)
    ).toBeInTheDocument();
    expect(streamSpy).toHaveBeenCalledWith(
      'مدارک کارت هوشمند ملی چیست؟',
      null,
      {},
      expect.any(Object)
    );
  });

  it('navigates appropriately when clicking suggested actions', () => {
    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    const officeAction = screen.getByText('نزدیک‌ترین دفاتر پیشخوان');
    fireEvent.click(officeAction);
    expect(mockedNavigate).toHaveBeenCalledWith('/map');

    const serviceAction = screen.getByText('مدارک کارت هوشمند ملی');
    fireEvent.click(serviceAction);
    expect(mockedNavigate).toHaveBeenCalledWith('/services/id-national-card');
  });

  it('starts new conversation when clicking refresh button', () => {
    render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    const refreshBtn = screen.getByTitle('گفتگو جدید');
    fireEvent.click(refreshBtn);

    expect(screen.getByText('مکالمه جدید آغاز شد. چه سوالی دارید؟')).toBeInTheDocument();
  });

  it('Strict Hard Constraint HC-5: Zero AI Provider Keys or direct API endpoints in client source code', () => {
    const srcDir = path.resolve(__dirname, '../../../');
    const forbiddenStrings = [
      'openrouter.ai',
      'api.openai.com',
      'api.anthropic.com',
      '@google/genai',
      'OPENROUTER_API_KEY',
      'OPENAI_API_KEY',
      'ANTHROPIC_API_KEY',
      'GEMINI_API_KEY',
    ];

    function scanDir(dir: string): string[] {
      let files: string[] = [];
      const entries = fs.readdirSync(dir, { withFileTypes: true });
      for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
          if (entry.name !== 'node_modules' && entry.name !== 'dist' && entry.name !== '.git') {
            files = files.concat(scanDir(fullPath));
          }
        } else if (/\.(ts|tsx|js|jsx)$/.test(entry.name)) {
          files.push(fullPath);
        }
      }
      return files;
    }

    const sourceFiles = scanDir(srcDir);
    expect(sourceFiles.length).toBeGreaterThan(10);

    for (const file of sourceFiles) {
      // Exclude this test file itself from the search
      if (file.includes('AiAssistant.test.tsx')) continue;

      const content = fs.readFileSync(file, 'utf8');
      for (const forbidden of forbiddenStrings) {
        expect(
          content.includes(forbidden),
          `Hard Constraint HC-5 VIOLATION: Found forbidden reference "${forbidden}" in client file: ${file}`
        ).toBe(false);
      }
    }
  });

  it('passes axe accessibility test with zero violations', async () => {
    const { container } = render(
      <MemoryRouter>
        <AiAssistantView />
      </MemoryRouter>
    );

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
