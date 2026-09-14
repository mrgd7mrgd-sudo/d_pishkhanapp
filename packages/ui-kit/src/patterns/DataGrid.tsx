import React, { useRef, useTransition, useState } from 'react';
import { useVirtualizer } from '@tanstack/react-virtual';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface DataGridColumn<T> {
  id: string;
  header: string;
  width?: string | number;
  minWidth?: number;
  cell: (item: T, index: number) => React.ReactNode;
  align?: 'start' | 'center' | 'end';
}

export interface DataGridProps<T> {
  data: T[];
  columns: DataGridColumn<T>[];
  keyExtractor: (item: T, index: number) => string;
  rowHeight?: number;
  maxHeight?: number | string;
  onRowClick?: (item: T) => void;
  selectedRowKey?: string | null;
  emptyMessage?: string;
  ariaLabel?: string;
  className?: string;
  isLoading?: boolean;
}

export function DataGrid<T>({
  data,
  columns,
  keyExtractor,
  rowHeight = 56,
  maxHeight = 600,
  onRowClick,
  selectedRowKey,
  emptyMessage = 'داده‌ای برای نمایش وجود ندارد.',
  ariaLabel = 'جدول اطلاعات',
  className,
  isLoading = false,
}: DataGridProps<T>): React.JSX.Element {
  const parentRef = useRef<HTMLDivElement>(null);
  const [, startTransition] = useTransition();
  const [focusedRowIndex, setFocusedRowIndex] = useState<number | null>(null);

  const rowVirtualizer = useVirtualizer({
    count: data.length,
    getScrollElement: () => parentRef.current,
    estimateSize: () => rowHeight,
    overscan: 5,
    initialRect: { width: 1000, height: typeof maxHeight === 'number' ? maxHeight : 600 },
  });

  const virtualItems = rowVirtualizer.getVirtualItems();
  const totalSize = rowVirtualizer.getTotalSize();

  const handleKeyDown = (e: React.KeyboardEvent, index: number, item: T) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onRowClick?.(item);
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      const next = Math.min(data.length - 1, index + 1);
      startTransition(() => {
        setFocusedRowIndex(next);
        rowVirtualizer.scrollToIndex(next, { align: 'auto' });
      });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prev = Math.max(0, index - 1);
      startTransition(() => {
        setFocusedRowIndex(prev);
        rowVirtualizer.scrollToIndex(prev, { align: 'auto' });
      });
    }
  };

  return (
    <div
      className={twMerge(
        'w-full bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden flex flex-col',
        className
      )}
      role="region"
      aria-label={ariaLabel}
    >
      <table className="w-full border-collapse" role="table">
        {/* Table Header */}
        <thead className="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 select-none">
          <tr role="row">
            {columns.map((col) => (
              <th
                key={col.id}
                scope="col"
                style={{
                  width: typeof col.width === 'number' ? `${col.width}px` : col.width || 'auto',
                  minWidth: col.minWidth ? `${col.minWidth}px` : undefined,
                }}
                className={clsx(
                  'px-4 py-3 truncate font-semibold',
                  col.align === 'center' && 'text-center',
                  col.align === 'end' && 'text-end',
                  (!col.align || col.align === 'start') && 'text-start'
                )}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
      </table>

      {/* Loading State */}
      {isLoading && (
        <div className="py-12 text-center text-sm text-slate-500 flex items-center justify-center gap-2">
          <span className="inline-block w-4 h-4 border-2 border-slate-400 border-t-slate-800 rounded-full animate-spin" />
          <span>در حال دریافت اطلاعات...</span>
        </div>
      )}

      {/* Empty State */}
      {!isLoading && data.length === 0 && (
        <div className="py-12 text-center text-sm text-slate-500 font-medium">{emptyMessage}</div>
      )}

      {/* Virtualized Body */}
      {!isLoading && data.length > 0 && (
        <div
          ref={parentRef}
          style={{
            maxHeight: typeof maxHeight === 'number' ? `${maxHeight}px` : maxHeight,
            overflowY: 'auto',
          }}
          className="relative w-full focus:outline-hidden"
        >
          <table
            className="w-full border-collapse"
            role="table"
            style={{ height: `${totalSize}px`, position: 'relative' }}
          >
            <tbody className="divide-y divide-slate-100">
              {(virtualItems.length > 0
                ? virtualItems
                : data.slice(0, 15).map((_, i) => ({ index: i, start: i * rowHeight }))
              ).map((virtualRow) => {
                const item = data[virtualRow.index];
                if (!item) return null;
                const key = keyExtractor(item, virtualRow.index);
                const isSelected = selectedRowKey === key;
                const isFocused = focusedRowIndex === virtualRow.index;

                return (
                  <tr
                    key={key}
                    data-index={virtualRow.index}
                    ref={rowVirtualizer.measureElement}
                    tabIndex={onRowClick ? 0 : undefined}
                    aria-selected={isSelected}
                    onClick={() => onRowClick?.(item)}
                    onKeyDown={(e) => handleKeyDown(e, virtualRow.index, item)}
                    style={{
                      position: 'absolute',
                      top: 0,
                      right: 0,
                      width: '100%',
                      display: 'flex',
                      transform: `translateY(${virtualRow.start}px)`,
                    }}
                    className={twMerge(
                      clsx(
                        'items-center px-2 transition-colors cursor-pointer text-sm',
                        virtualRow.index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40',
                        'hover:bg-slate-100/70',
                        isSelected &&
                          'bg-blue-50/80 hover:bg-blue-50 border-r-4 border-blue-600 font-medium',
                        isFocused && 'ring-2 ring-inset ring-blue-500'
                      )
                    )}
                  >
                    {columns.map((col) => (
                      <td
                        key={col.id}
                        style={{
                          width:
                            typeof col.width === 'number' ? `${col.width}px` : col.width || 'auto',
                          flex: col.width ? 'none' : 1,
                          minWidth: col.minWidth ? `${col.minWidth}px` : undefined,
                        }}
                        className={clsx(
                          'px-2 py-3 truncate',
                          col.align === 'center' && 'text-center',
                          col.align === 'end' && 'text-end',
                          (!col.align || col.align === 'start') && 'text-start'
                        )}
                      >
                        {col.cell(item, virtualRow.index)}
                      </td>
                    ))}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
