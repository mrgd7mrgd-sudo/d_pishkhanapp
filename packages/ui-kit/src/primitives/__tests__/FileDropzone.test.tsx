import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { FileDropzone } from '../FileDropzone';

describe('FileDropzone Primitive Component (§4.8, TASK-062, TASK-062-T)', () => {
  it('renders default dropzone with accessible elements', () => {
    render(
      <FileDropzone
        onFileSelect={vi.fn()}
        label="آپلود تصویر شناسنامه"
        description="فرمت‌های مجاز JPG یا PDF تا ۱۰ مگابایت"
      />
    );

    expect(screen.getByText('آپلود تصویر شناسنامه')).toBeInTheDocument();
    expect(screen.getByText('فرمت‌های مجاز JPG یا PDF تا ۱۰ مگابایت')).toBeInTheDocument();
    expect(screen.getByTestId('file-dropzone-input')).toBeInTheDocument();
  });

  it('selects valid file on input change and calls onFileSelect', () => {
    const handleSelect = vi.fn();
    render(<FileDropzone onFileSelect={handleSelect} accept="image/jpeg,image/png" />);

    const file = new File(['dummy-content'], 'id-card.jpg', { type: 'image/jpeg' });
    const input = screen.getByTestId('file-dropzone-input');

    fireEvent.change(input, { target: { files: [file] } });
    expect(handleSelect).toHaveBeenCalledWith(file);
  });

  it('rejects file larger than maxSize and shows error message', () => {
    const handleSelect = vi.fn();
    render(<FileDropzone onFileSelect={handleSelect} maxSize={1024} />);

    const largeFile = new File(['a'.repeat(2048)], 'big.jpg', { type: 'image/jpeg' });
    const input = screen.getByTestId('file-dropzone-input');

    fireEvent.change(input, { target: { files: [largeFile] } });
    expect(handleSelect).not.toHaveBeenCalled();
    expect(screen.getByText(/بیشتر از سقف مجاز/)).toBeInTheDocument();
  });

  it('rejects file with disallowed mime type', () => {
    const handleSelect = vi.fn();
    render(<FileDropzone onFileSelect={handleSelect} accept="image/jpeg" />);

    const invalidFile = new File(['dummy'], 'doc.exe', { type: 'application/x-msdownload' });
    const input = screen.getByTestId('file-dropzone-input');

    fireEvent.change(input, { target: { files: [invalidFile] } });
    expect(handleSelect).not.toHaveBeenCalled();
    expect(screen.getByText('فرمت فایل انتخابی مجاز نیست.')).toBeInTheDocument();
  });

  it('renders upload progress bar when isUploading is true', () => {
    render(<FileDropzone onFileSelect={vi.fn()} isUploading uploadProgress={65} />);

    const progressbar = screen.getByRole('progressbar');
    expect(progressbar).toBeInTheDocument();
    expect(progressbar.getAttribute('aria-valuenow')).toBe('65');
    expect(screen.getByText('65%')).toBeInTheDocument();
  });

  it('renders selected file name with clear action', () => {
    const handleClear = vi.fn();
    render(
      <FileDropzone
        onFileSelect={vi.fn()}
        selectedFileName="melli-card.png"
        onClear={handleClear}
      />
    );

    expect(screen.getByText('melli-card.png')).toBeInTheDocument();
    const clearBtn = screen.getByLabelText('حذف فایل انتخابی');
    fireEvent.click(clearBtn);
    expect(handleClear).toHaveBeenCalledTimes(1);
  });

  it('passes axe accessibility checks with 0 violations', async () => {
    const { container } = render(
      <FileDropzone
        onFileSelect={vi.fn()}
        label="آپلود مدارک هویتی"
        description="تصویر کارت ملی هوشمند"
      />
    );

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
