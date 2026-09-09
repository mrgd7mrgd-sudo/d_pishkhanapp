import React, { useState } from 'react';
import { clsx } from 'clsx';

export interface ResponsiveImageProps {
  src: string;
  alt: string;
  width: number;
  height: number;
  avifSrcSet?: string | undefined;
  webpSrcSet?: string | undefined;
  jpegSrcSet?: string | undefined;
  sizes?: string | undefined;
  loading?: 'lazy' | 'eager' | undefined;
  decoding?: 'async' | 'sync' | 'auto' | undefined;
  fetchPriority?: 'high' | 'low' | 'auto' | undefined;
  blurhash?: string | undefined;
  className?: string | undefined;
  imageClassName?: string | undefined;
  onLoad?: (() => void) | undefined;
  onError?: (() => void) | undefined;
}

const PlaceholderView: React.FC<{ blurhash?: string | undefined; isLoaded: boolean }> = ({
  blurhash,
  isLoaded,
}) => {
  if (isLoaded) return null;

  return (
    <div
      data-testid="responsive-image-placeholder"
      aria-hidden="true"
      data-blurhash={blurhash}
      className="absolute inset-0 bg-slate-100 dark:bg-slate-800 animate-pulse transition-opacity duration-300"
    />
  );
};

export const ResponsiveImage: React.FC<ResponsiveImageProps> = ({
  src,
  alt,
  width,
  height,
  avifSrcSet,
  webpSrcSet,
  jpegSrcSet,
  sizes = '(max-width: 640px) 320px, (max-width: 1024px) 640px, 1024px',
  loading = 'lazy',
  decoding = 'async',
  fetchPriority,
  blurhash,
  className,
  imageClassName,
  onLoad,
  onError,
}) => {
  const [isLoaded, setIsLoaded] = useState(false);

  const handleLoad = () => {
    setIsLoaded(true);
    onLoad?.();
  };

  return (
    <div
      data-testid="responsive-image-container"
      className={clsx('relative overflow-hidden bg-slate-100 dark:bg-slate-800', className)}
      style={{ aspectRatio: `${width} / ${height}` }}
    >
      <PlaceholderView blurhash={blurhash} isLoaded={isLoaded} />

      <picture>
        {avifSrcSet && <source srcSet={avifSrcSet} sizes={sizes} type="image/avif" />}
        {webpSrcSet && <source srcSet={webpSrcSet} sizes={sizes} type="image/webp" />}
        {jpegSrcSet && <source srcSet={jpegSrcSet} sizes={sizes} type="image/jpeg" />}
        <img
          src={src}
          alt={alt}
          width={width}
          height={height}
          loading={loading}
          decoding={decoding}
          fetchPriority={fetchPriority}
          onLoad={handleLoad}
          onError={onError}
          className={clsx(
            'w-full h-full object-cover transition-opacity duration-300',
            isLoaded ? 'opacity-100' : 'opacity-0',
            imageClassName,
          )}
        />
      </picture>
    </div>
  );
};
