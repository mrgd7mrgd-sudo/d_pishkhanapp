import React, {
  useCallback,
  useEffect,
  useMemo,
  useLayoutEffect,
  useRef,
  useState,
  type CSSProperties,
  type MutableRefObject,
  type ReactNode,
} from 'react';
import { 
  Home, 
  LayoutGrid, 
  MapPin, 
  FolderClock, 
  User 
} from 'lucide-react';
import styles from './LiquidTabBar.module.css';
import { CaseRequest } from '../types';

export type NavTab = 'home' | 'services' | 'map' | 'cases' | 'profile';

export type LiquidTabItem = {
  id: NavTab;
  label: string;
  icon: ReactNode;
  badge?: boolean | number;
};

interface NavbarProps {
  activeTab: NavTab;
  onChangeTab: (tab: NavTab) => void;
  cases: CaseRequest[];
}

/* Lens geometry in px for fluid water-drop feel */
const REST_H = 48;
const REST_R = 24;
const TRAVEL_W = 72;
const TRAVEL_H = 52;
const TRAVEL_R = 26;

/* Must match --lq-dur in the stylesheet for continuous fluid flow */
const TRAVEL_MS = 500;
const SETTLE_AT = 500;

type Rect = { left: number; width: number };

const useIsomorphicLayoutEffect = typeof window === 'undefined' ? useEffect : useLayoutEffect;

export const Navbar: React.FC<NavbarProps> = ({
  activeTab,
  onChangeTab,
  cases,
  onOpenSearch
}) => {
  const actionRequiredCount = cases.filter(c => c.status === 'action_required').length;
  const inProgressCount = cases.filter(c => c.status !== 'completed' && c.status !== 'action_required').length;

  const barRef = useRef<HTMLDivElement>(null);
  const tabRefs = useRef<(HTMLButtonElement | null)[]>([]);
  const rampRefs = useRef<(SVGAnimateElement | null)[]>([]);
  const settleTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const [rects, setRects] = useState<Rect[]>([]);
  const [barWidth, setBarWidth] = useState(0);
  const [travelling, setTravelling] = useState(false);

  const tabs: LiquidTabItem[] = useMemo(() => [
    {
      id: 'home',
      label: 'خانه',
      icon: <Home className="w-[22px] h-[22px] stroke-[2.2]" />,
    },
    {
      id: 'services',
      label: 'خدمات',
      icon: <LayoutGrid className="w-[22px] h-[22px] stroke-[2.2]" />,
    },
    {
      id: 'map',
      label: 'نقشه',
      icon: <MapPin className="w-[22px] h-[22px] stroke-[2.2]" />,
    },
    {
      id: 'cases',
      label: 'پیگیری',
      icon: <FolderClock className="w-[22px] h-[22px] stroke-[2.2]" />,
      badge: actionRequiredCount > 0 ? true : inProgressCount > 0 ? inProgressCount : undefined,
    },
    {
      id: 'profile',
      label: 'پروفایل',
      icon: <User className="w-[22px] h-[22px] stroke-[2.2]" />,
    },
  ], [actionRequiredCount, inProgressCount]);

  const activeIndex = Math.max(
    0,
    tabs.findIndex((tab) => tab.id === activeTab),
  );

  /* Geometry calculation from measured rects for reliable positioning */
  const measure = useCallback(() => {
    const bar = barRef.current;
    if (!bar) return;
    const barBox = bar.getBoundingClientRect();
    const scale = bar.offsetWidth > 0 ? barBox.width / bar.offsetWidth : 1;
    setBarWidth(barBox.width / scale);
    setRects(
      tabs.map((_, i) => {
        const box = tabRefs.current[i]?.getBoundingClientRect();
        if (!box) return { left: 0, width: 0 };
        return { left: (box.left - barBox.left) / scale, width: box.width / scale };
      }),
    );
  }, [tabs]);

  useIsomorphicLayoutEffect(() => {
    measure();
    const bar = barRef.current;
    if (!bar || typeof ResizeObserver === 'undefined') return;
    const observer = new ResizeObserver(measure);
    observer.observe(bar);
    return () => observer.disconnect();
  }, [measure]);

  useEffect(
    () => () => {
      if (settleTimer.current) clearTimeout(settleTimer.current);
    },
    [],
  );

  const select = (id: NavTab) => {
    if (id === activeTab) return;
    onChangeTab(id);

    if (
      typeof window !== 'undefined' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
      return;
    }

    setTravelling(true);
    // Restart the displacement ramp from zero on every hop
    for (const ramp of rampRefs.current) ramp?.beginElement?.();

    if (settleTimer.current) clearTimeout(settleTimer.current);
    settleTimer.current = setTimeout(() => setTravelling(false), SETTLE_AT);
  };

  const active = rects[activeIndex];

  const lensStyle = useMemo<CSSProperties>(() => {
    if (!active) return {};
    const width = travelling ? TRAVEL_W : active.width;
    const centre = active.left + active.width / 2;
    return {
      '--lens-x': `${centre - width / 2}px`,
      '--lens-w': `${width}px`,
      '--lens-h': `${travelling ? TRAVEL_H : REST_H}px`,
      '--lens-r': `${travelling ? TRAVEL_R : REST_R}px`,
      '--bar-w': `${barWidth}px`,
    } as CSSProperties;
  }, [active, barWidth, travelling]);

  const renderTab = (tab: LiquidTabItem, index: number, isClone: boolean) => {
    const inner = (
      <>
        <span className="relative inline-flex items-center justify-center">
          <span className={styles.icon}>{tab.icon}</span>
          {tab.badge !== undefined && tab.badge !== false ? (
            <span className={styles.badge}>{tab.badge === true ? '!' : tab.badge}</span>
          ) : null}
        </span>
        <span className={styles.label}>{tab.label}</span>
      </>
    );

    if (isClone) {
      return (
        <span key={tab.id} className={styles.tab} aria-hidden="true">
          {inner}
        </span>
      );
    }

    return (
      <button
        key={tab.id}
        ref={(node) => {
          tabRefs.current[index] = node;
        }}
        type="button"
        role="tab"
        aria-selected={index === activeIndex}
        className={styles.tab}
        onClick={() => select(tab.id)}
      >
        {inner}
      </button>
    );
  };

  return (
    <div className="fixed bottom-3 sm:bottom-5 inset-x-2.5 sm:inset-x-4 z-40 max-w-lg mx-auto pointer-events-none select-none flex items-center justify-center gap-2">
      <div className={[styles.root, 'pointer-events-auto flex items-center gap-2 w-full'].join(' ')}>
        <LiquidGlassDefs rampRefs={rampRefs} />

        <div ref={barRef} className={`${styles.bar} flex-1`} style={lensStyle}>
          <div className={styles.row} role="tablist" aria-label="بخش‌های پیشخوان">
            {tabs.map((tab, i) => renderTab(tab, i, false))}
          </div>

          {/* The lens. Sits above the row, paints recoloured Telegram Blue + refracted copy */}
          <div
            className={styles.lens}
            data-travel={travelling}
            data-ready={Boolean(active)}
            aria-hidden="true"
          >
            <div className={styles.fill} />
            <div className={styles.window}>
              <div className={styles.cloneShell}>
                <div className={styles.warp}>
                  <div className={styles.clone}>{tabs.map((tab, i) => renderTab(tab, i, true))}</div>
                </div>
              </div>
            </div>
            <div className={styles.rim} />
          </div>
        </div>
      </div>
    </div>
  );
};

/*
 * Refraction filter.
 *
 * A smoothed fractal-noise field drives three independent displacement passes —
 * one per colour channel, at falling strengths — which are then screened back
 * together.
 */
function LiquidGlassDefs({
  rampRefs,
}: {
  rampRefs: MutableRefObject<(SVGAnimateElement | null)[]>;
}) {
  const peaks = [13, 10.5, 8];

  return (
    <svg className={styles.defs} aria-hidden="true" focusable="false">
      <defs>
        <filter
          id="lqWarp"
          x="-30%"
          y="-30%"
          width="160%"
          height="160%"
          colorInterpolationFilters="sRGB"
        >
          <feTurbulence
            type="fractalNoise"
            baseFrequency="0.020 0.028"
            numOctaves={1}
            seed={12}
            result="noise"
          />
          <feGaussianBlur in="noise" stdDeviation="0.8" result="field" />

          <feColorMatrix
            in="SourceGraphic"
            type="matrix"
            values="1 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1 0"
            result="chR"
          />
          <feColorMatrix
            in="SourceGraphic"
            type="matrix"
            values="0 0 0 0 0  0 1 0 0 0  0 0 0 0 0  0 0 0 1 0"
            result="chG"
          />
          <feColorMatrix
            in="SourceGraphic"
            type="matrix"
            values="0 0 0 0 0  0 0 0 0 0  0 0 1 0 0  0 0 0 1 0"
            result="chB"
          />

          {(['chR', 'chG', 'chB'] as const).map((channel, i) => (
            <feDisplacementMap
              key={channel}
              in={channel}
              in2="field"
              scale={0}
              xChannelSelector="R"
              yChannelSelector="G"
              result={`d${channel}`}
            >
              <animate
                ref={(node) => {
                  rampRefs.current[i] = node as SVGAnimateElement | null;
                }}
                attributeName="scale"
                values={`0;${peaks[i]};${peaks[i]};0`}
                keyTimes="0;0.16;0.68;1"
                dur={`${TRAVEL_MS}ms`}
                begin="indefinite"
                fill="freeze"
              />
            </feDisplacementMap>
          ))}

          <feBlend in="dchR" in2="dchG" mode="screen" result="rg" />
          <feBlend in="rg" in2="dchB" mode="screen" />
        </filter>
      </defs>
    </svg>
  );
}

export default Navbar;
