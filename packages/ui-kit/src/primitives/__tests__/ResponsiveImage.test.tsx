import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { ResponsiveImage } from '../ResponsiveImage';

describe('ResponsiveImage Component (§4.8, TASK-046, TASK-046-T)', () => {
  const defaultProps = {
    src: '/images/test-640.jpg',
    alt: 'تصویر نمونه کارت خدمت',
    width: 640,
    height: 360,
    avifSrcSet: '/images/test-320.avif 320w, /images/test-640.avif 640w',
    webpSrcSet: '/images/test-320.webp 320w, /images/test-640.webp 640w',
    jpegSrcSet: '/images/test-320.jpg 320w, /images/test-640.jpg 640w',
    blurhash: 'L6PZfSi_.AyE',
  };

  it('renders picture container with explicit aspect ratio to ensure CLS = 0', () => {
    render(<ResponsiveImage {...defaultProps} />);

    const container = screen.getByTestId('responsive-image-container');
    expect(container).toBeInTheDocument();
    expect(container.style.aspectRatio).toBe('640 / 360');
  });

  it('renders source elements in optimal format order (AVIF -> WebP -> JPEG)', () => {
    const { container } = render(<ResponsiveImage {...defaultProps} />);

    const sources = container.querySelectorAll('picture source');
    expect(sources).toHaveLength(3);

    expect(sources[0]?.getAttribute('type')).toBe('image/avif');
    expect(sources[0]?.getAttribute('srcset')).toBe(defaultProps.avifSrcSet);

    expect(sources[1]?.getAttribute('type')).toBe('image/webp');
    expect(sources[1]?.getAttribute('srcset')).toBe(defaultProps.webpSrcSet);

    expect(sources[2]?.getAttribute('type')).toBe('image/jpeg');
    expect(sources[2]?.getAttribute('srcset')).toBe(defaultProps.jpegSrcSet);
  });

  it('renders img element with explicit width, height, lazy loading, and async decoding', () => {
    render(<ResponsiveImage {...defaultProps} />);

    const img = screen.getByRole('img', { name: defaultProps.alt });
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('width', '640');
    expect(img).toHaveAttribute('height', '360');
    expect(img).toHaveAttribute('loading', 'lazy');
    expect(img).toHaveAttribute('decoding', 'async');
    expect(img).toHaveAttribute('src', defaultProps.src);
  });

  it('renders blurhash placeholder and transitions to loaded state on image load', () => {
    const handleLoad = vi.fn();
    render(<ResponsiveImage {...defaultProps} onLoad={handleLoad} />);

    const placeholder = screen.getByTestId('responsive-image-placeholder');
    expect(placeholder).toBeInTheDocument();
    expect(placeholder).toHaveAttribute('data-blurhash', defaultProps.blurhash);

    const img = screen.getByRole('img', { name: defaultProps.alt });
    fireEvent.load(img);

    expect(handleLoad).toHaveBeenCalledTimes(1);
    expect(screen.queryByTestId('responsive-image-placeholder')).not.toBeInTheDocument();
  });

  it('supports eager loading and high fetchpriority for above-the-fold hero images', () => {
    render(
      <ResponsiveImage
        {...defaultProps}
        loading="eager"
        fetchPriority="high"
      />,
    );

    const img = screen.getByRole('img', { name: defaultProps.alt });
    expect(img).toHaveAttribute('loading', 'eager');
    expect(img).toHaveAttribute('fetchpriority', 'high');
  });

  it('satisfies accessibility requirements with 0 axe violations', async () => {
    const { container } = render(<ResponsiveImage {...defaultProps} />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
