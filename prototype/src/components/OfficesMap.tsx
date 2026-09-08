import React, { useState, useEffect, useRef, useMemo } from 'react';
import { createPortal } from 'react-dom';
import { 
  MapPin, 
  Navigation, 
  Phone, 
  Clock, 
  Star, 
  Users, 
  Sparkles, 
  Zap, 
  Calendar, 
  CheckCircle2, 
  ChevronLeft,
  ChevronDown,
  ChevronUp,
  ChevronsUpDown,
  List,
  X,
  SlidersHorizontal,
  Award,
  Search,
  LocateFixed,
  Store,
  Layers,
  Map as MapIcon,
  Compass,
  Info,
  ArrowRight,
  ShieldCheck,
  Bot,
  AlertTriangle,
  Radio,
  Building,
  ExternalLink,
  ShieldAlert,
  Moon,
  Plus,
  Minus
} from 'lucide-react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { PishkhanOffice, CitizenService, OfficeMembershipStatus } from '../types';

interface OfficesMapProps {
  offices: PishkhanOffice[];
  services: CitizenService[];
  onDirectAssign: (office: PishkhanOffice) => void;
  onBookAppointment: (office: PishkhanOffice) => void;
  onOpenChatbot: () => void;
}

// User current location (Tehran center/Valiasr)
const USER_LOCATION = {
  lat: 35.7480,
  lng: 51.4120
};

// Map Tile Layers for Street Views
const MAP_THEMES = {
  osm_streets: {
    name: 'خیابان استاندارد (OSM)',
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    subdomains: ['a', 'b', 'c'],
    attribution: '&copy; OpenStreetMap contributors'
  },
  voyager: {
    name: 'استریت مدرن (CartoDB)',
    url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    subdomains: ['a', 'b', 'c', 'd'],
    attribution: '&copy; CARTO'
  },
  positron: {
    name: 'مینیمال روشن',
    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    subdomains: ['a', 'b', 'c', 'd'],
    attribution: '&copy; CARTO'
  }
};

// Helper to determine office membership category
export const getOfficeStatus = (office: PishkhanOffice): OfficeMembershipStatus => {
  if (office.membershipStatus) return office.membershipStatus;
  return office.isOnline ? 'registered_online' : 'registered_offline';
};

export const OfficesMap: React.FC<OfficesMapProps> = ({
  offices,
  services,
  onDirectAssign,
  onBookAppointment,
  onOpenChatbot
}) => {
  const [selectedOffice, setSelectedOffice] = useState<PishkhanOffice>(offices[0]);
  const [filterChip, setFilterChip] = useState<'all' | 'registered_online' | 'registered_offline' | 'unregistered' | 'nearest' | 'top_rated'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [showFullDetailsModal, setShowFullDetailsModal] = useState(false);
  const [isRoutingActive, setIsRoutingActive] = useState(false);
  const [copiedPhone, setCopiedPhone] = useState(false);
  const [modalTab, setModalTab] = useState<'info' | 'services' | 'reviews'>('info');
  const [mapTheme, setMapTheme] = useState<keyof typeof MAP_THEMES>('osm_streets');
  const [showThemePicker, setShowThemePicker] = useState(false);
  const [showFilters, setShowFilters] = useState(true);
  const [showLegend, setShowLegend] = useState(false);
  const [isDrawerExpanded, setIsDrawerExpanded] = useState(true);

  const mapContainerRef = useRef<HTMLDivElement>(null);
  const mapInstanceRef = useRef<L.Map | null>(null);
  const markersRef = useRef<Record<string, L.Marker>>({});
  const routeLineRef = useRef<L.Polyline | null>(null);
  const tileLayerRef = useRef<L.TileLayer | null>(null);

  // Group office counts
  const onlineCount = useMemo(() => offices.filter(o => getOfficeStatus(o) === 'registered_online').length, [offices]);
  const offlineCount = useMemo(() => offices.filter(o => getOfficeStatus(o) === 'registered_offline').length, [offices]);
  const unregisteredCount = useMemo(() => offices.filter(o => getOfficeStatus(o) === 'unregistered').length, [offices]);

  // Filter logic
  const filteredOffices = useMemo(() => {
    return offices.filter(office => {
      const status = getOfficeStatus(office);
      if (filterChip === 'registered_online' && status !== 'registered_online') return false;
      if (filterChip === 'registered_offline' && status !== 'registered_offline') return false;
      if (filterChip === 'unregistered' && status !== 'unregistered') return false;
      if (filterChip === 'top_rated' && office.rating < 4.7) return false;

      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        return (office.name || '').toLowerCase().includes(q) || 
               (office.code || '').toLowerCase().includes(q) || 
               (office.address || '').toLowerCase().includes(q) ||
               (office.region || '').toLowerCase().includes(q);
      }
      return true;
    }).sort((a, b) => {
      if (filterChip === 'nearest') return a.distanceKm - b.distanceKm;
      if (filterChip === 'top_rated') return b.rating - a.rating;
      return a.distanceKm - b.distanceKm;
    });
  }, [offices, filterChip, searchQuery]);

  // Create custom high-fidelity marker icons for the 3 distinct categories
  const createOfficeIcon = (office: PishkhanOffice, isSelected: boolean) => {
    const status = getOfficeStatus(office);

    // ==========================================
    // 1. REGISTERED & ONLINE (عضو پلتفرم و آنلاین)
    // ==========================================
    if (status === 'registered_online') {
      if (isSelected) {
        return L.divIcon({
          className: 'custom-pishkhan-selected-online-pin',
          html: `
            <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full cursor-pointer animate-in zoom-in-75 duration-200">
              <!-- Expanded Active Label -->
              <div class="mb-1 bg-[#104b2b] text-white text-[11px] font-black px-3.5 py-1.5 rounded-2xl shadow-2xl whitespace-nowrap flex items-center gap-2 border border-emerald-400/50 ring-2 ring-emerald-500/40">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                </span>
                <span class="font-extrabold">${office.name}</span>
                <span class="bg-emerald-500/30 text-emerald-200 text-[9px] px-1.5 py-0.5 rounded-md font-mono">آنلاین</span>
              </div>
              
              <!-- Luxury Glowing Marker Body -->
              <div class="relative w-13 h-13 rounded-2xl bg-gradient-to-br from-[#20bf6b] via-[#1ea858] to-[#107038] text-white shadow-[0_10px_25px_rgba(30,168,88,0.55)] ring-4 ring-emerald-400/40 flex items-center justify-center border-2 border-white">
                <!-- Inner Government Badge Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white drop-shadow-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/>
                  <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                  <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/>
                  <path d="M2 7h20"/>
                  <circle cx="12" cy="11" r="1.5" fill="currentColor"/>
                </svg>
                <!-- Online Star/Zap Indicator Badge on Corner -->
                <span class="absolute -top-1.5 -right-1.5 w-4.5 h-4.5 rounded-full bg-amber-400 border-2 border-white flex items-center justify-center text-[9px] font-black text-slate-950 shadow-sm">⚡</span>
              </div>
              <!-- Pin Pointer Tip -->
              <div class="w-3.5 h-3.5 bg-[#107038] rotate-45 -mt-1.5 ring-2 ring-white shadow-md"></div>
            </div>
          `,
          iconSize: [52, 76],
          iconAnchor: [26, 76],
        });
      }

      // Unselected Online Marker
      return L.divIcon({
        className: 'custom-pishkhan-online-pin',
        html: `
          <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full group cursor-pointer">
            <!-- Pulsing Beacon ring -->
            <div class="absolute -inset-1 rounded-2xl bg-emerald-400/30 blur-[2px] animate-pulse"></div>
            
            <div class="relative w-10 h-10 rounded-2xl bg-white text-[#1ea858] shadow-[0_4px_16px_rgba(30,168,88,0.25)] border-2 border-emerald-500 hover:border-emerald-600 hover:scale-115 flex items-center justify-center transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#1ea858]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/>
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/>
                <path d="M2 7h20"/>
              </svg>
              <!-- Live Green Dot -->
              <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white ring-1 ring-emerald-300"></span>
            </div>
            <div class="w-2.5 h-2.5 bg-white rotate-45 -mt-1 shadow-xs border-r-2 border-b-2 border-emerald-500"></div>
          </div>
        `,
        iconSize: [40, 50],
        iconAnchor: [20, 50],
      });
    }

    // ==========================================
    // 2. REGISTERED & OFFLINE (عضو پلتفرم و آفلاین)
    // ==========================================
    if (status === 'registered_offline') {
      if (isSelected) {
        return L.divIcon({
          className: 'custom-pishkhan-selected-offline-pin',
          html: `
            <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full cursor-pointer animate-in zoom-in-75 duration-200">
              <div class="mb-1 bg-slate-900 text-white text-[11px] font-black px-3 py-1 rounded-2xl shadow-xl whitespace-nowrap flex items-center gap-1.5 border border-slate-600 ring-2 ring-slate-500/30">
                <span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>
                <span>${office.name}</span>
                <span class="text-slate-300 text-[9px] bg-slate-800 px-1 py-0.5 rounded">عضو سامانه (آفلاین)</span>
              </div>
              <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 text-white shadow-xl ring-4 ring-slate-400/30 flex items-center justify-center border-2 border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
                <!-- Offline clock tag on corner -->
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-500 border border-white flex items-center justify-center text-[8px]">🕒</span>
              </div>
              <div class="w-3 h-3 bg-slate-800 rotate-45 -mt-1.5 ring-2 ring-white"></div>
            </div>
          `,
          iconSize: [48, 72],
          iconAnchor: [24, 72],
        });
      }

      // Unselected Offline Marker
      return L.divIcon({
        className: 'custom-pishkhan-offline-pin',
        html: `
          <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full group cursor-pointer opacity-90 hover:opacity-100">
            <div class="w-8.5 h-8.5 rounded-2xl bg-slate-100 text-slate-500 shadow-md border-2 border-slate-400 hover:border-slate-700 hover:scale-115 flex items-center justify-center transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
              </svg>
              <!-- Grey dot -->
              <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-slate-400 border border-white"></span>
            </div>
            <div class="w-2.5 h-2.5 bg-slate-100 rotate-45 -mt-1 shadow-xs border-r border-b border-slate-400"></div>
          </div>
        `,
        iconSize: [34, 44],
        iconAnchor: [17, 44],
      });
    }

    // ==========================================
    // 3. UNREGISTERED (غیرعضو در پلتفرم / سنتی)
    // ==========================================
    if (isSelected) {
      return L.divIcon({
        className: 'custom-pishkhan-selected-unregistered-pin',
        html: `
          <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full cursor-pointer animate-in zoom-in-75 duration-200">
            <div class="mb-1 bg-amber-950 text-amber-100 text-[11px] font-black px-3 py-1 rounded-2xl shadow-xl whitespace-nowrap flex items-center gap-1.5 border border-amber-500/60 ring-2 ring-amber-500/30">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <span>${office.name}</span>
              <span class="text-amber-300 text-[9px] bg-amber-900/80 px-1.5 py-0.5 rounded">غیرعضو در سامانه</span>
            </div>
            <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-amber-700 text-white shadow-[0_10px_25px_rgba(217,119,6,0.45)] ring-4 ring-amber-400/40 flex items-center justify-center border-2 border-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white drop-shadow-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                <path d="M10 6h4"/>
                <path d="M10 10h4"/>
                <path d="M10 14h4"/>
                <path d="M10 18h4"/>
              </svg>
              <!-- Alert icon on corner -->
              <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-amber-300 border border-white flex items-center justify-center text-[9px] font-black text-amber-950">!</span>
            </div>
            <div class="w-3 h-3 bg-orange-600 rotate-45 -mt-1.5 ring-2 ring-white"></div>
          </div>
        `,
        iconSize: [48, 72],
        iconAnchor: [24, 72],
      });
    }

    // Unselected Unregistered Marker
    return L.divIcon({
      className: 'custom-pishkhan-unregistered-pin',
      html: `
        <div class="relative flex flex-col items-center -translate-x-1/2 -translate-y-full group cursor-pointer">
          <div class="w-8.5 h-8.5 rounded-2xl bg-amber-50 text-amber-700 shadow-md border-2 border-dashed border-amber-500 hover:border-amber-700 hover:scale-115 flex items-center justify-center transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
              <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
            </svg>
            <!-- Orange Alert Badge -->
            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-500 border border-white"></span>
          </div>
          <div class="w-2.5 h-2.5 bg-amber-50 rotate-45 -mt-1 shadow-xs border-r-2 border-b-2 border-amber-500"></div>
        </div>
      `,
      iconSize: [34, 44],
      iconAnchor: [17, 44],
    });
  };

  const createUserLocationIcon = () => {
    return L.divIcon({
      className: 'custom-user-pin',
      html: `
        <div class="relative flex items-center justify-center -translate-x-1/2 -translate-y-1/2">
          <div class="w-9 h-9 rounded-full bg-blue-500/25 animate-ping absolute"></div>
          <div class="w-6 h-6 rounded-full bg-blue-600 ring-4 ring-white shadow-xl flex items-center justify-center text-white">
            <div class="w-2 h-2 rounded-full bg-white"></div>
          </div>
        </div>
      `,
      iconSize: [36, 36],
      iconAnchor: [18, 18],
    });
  };

  // Initialize Map
  useEffect(() => {
    if (!mapContainerRef.current) return;
    if (mapInstanceRef.current) return;

    const map = L.map(mapContainerRef.current, {
      center: [selectedOffice.coords.lat, selectedOffice.coords.lng],
      zoom: 13.5,
      zoomControl: false,
      attributionControl: false
    });

    // Initial Tile Layer
    const tileLayer = L.tileLayer(MAP_THEMES[mapTheme].url, {
      subdomains: MAP_THEMES[mapTheme].subdomains,
      maxZoom: 19
    }).addTo(map);

    tileLayerRef.current = tileLayer;

    // Add User Current Location Marker
    L.marker([USER_LOCATION.lat, USER_LOCATION.lng], {
      icon: createUserLocationIcon(),
      zIndexOffset: 1000
    })
      .addTo(map)
      .bindPopup('<div class="font-sans font-bold text-xs text-right p-1">📍 موقعیت فعلی شما (ولی‌عصر)</div>');

    mapInstanceRef.current = map;

    // Ensure map tiles load cleanly across all viewport changes
    const forceInvalidate = () => {
      map.invalidateSize();
    };

    forceInvalidate();
    const t1 = setTimeout(forceInvalidate, 150);
    const t2 = setTimeout(forceInvalidate, 400);
    const t3 = setTimeout(forceInvalidate, 800);

    let resizeObserver: ResizeObserver | null = null;
    if (mapContainerRef.current) {
      resizeObserver = new ResizeObserver(() => {
        forceInvalidate();
      });
      resizeObserver.observe(mapContainerRef.current);
    }

    window.addEventListener('resize', forceInvalidate);

    return () => {
      clearTimeout(t1);
      clearTimeout(t2);
      clearTimeout(t3);
      resizeObserver?.disconnect();
      window.removeEventListener('resize', forceInvalidate);
      map.remove();
      mapInstanceRef.current = null;
    };
  }, []);

  // Update Tile Layer when theme changes
  useEffect(() => {
    if (!mapInstanceRef.current || !tileLayerRef.current) return;
    tileLayerRef.current.setUrl(MAP_THEMES[mapTheme].url);
  }, [mapTheme]);

  // Update Markers on filtered offices change or selection change
  useEffect(() => {
    if (!mapInstanceRef.current) return;
    const map = mapInstanceRef.current;

    // Clear old office markers
    Object.values(markersRef.current).forEach((marker: L.Marker) => marker.remove());
    markersRef.current = {};

    // Add fresh markers
    filteredOffices.forEach(office => {
      const isSelected = selectedOffice?.id === office.id;
      const marker = L.marker([office.coords.lat, office.coords.lng], {
        icon: createOfficeIcon(office, isSelected),
        zIndexOffset: isSelected ? 500 : 100
      }).addTo(map);

      marker.on('click', () => {
        setSelectedOffice(office);
        map.flyTo([office.coords.lat, office.coords.lng], 14.5, {
          duration: 0.8
        });
      });

      markersRef.current[office.id] = marker;
    });

    // Clear route if office changes and not explicitly routing
    if (!isRoutingActive && routeLineRef.current) {
      routeLineRef.current.remove();
      routeLineRef.current = null;
    }
  }, [filteredOffices, selectedOffice, isRoutingActive]);

  // Handle Smart Routing Draw
  const handleStartRouting = () => {
    if (!mapInstanceRef.current || !selectedOffice) return;
    const map = mapInstanceRef.current;

    setIsRoutingActive(true);

    // Remove existing line if any
    if (routeLineRef.current) {
      routeLineRef.current.remove();
    }

    // Realistic multi-point simulated route coordinates
    const start: [number, number] = [USER_LOCATION.lat, USER_LOCATION.lng];
    const end: [number, number] = [selectedOffice.coords.lat, selectedOffice.coords.lng];
    
    // Intermediate waypoint for realistic street turn
    const mid1: [number, number] = [
      start[0] + (end[0] - start[0]) * 0.45 + 0.003,
      start[1] + (end[1] - start[1]) * 0.35 - 0.002
    ];
    const mid2: [number, number] = [
      start[0] + (end[0] - start[0]) * 0.75 - 0.001,
      start[1] + (end[1] - start[1]) * 0.80 + 0.002
    ];

    const polyline = L.polyline([start, mid1, mid2, end], {
      color: '#1ea858',
      weight: 5,
      opacity: 0.85,
      dashArray: '10, 8',
      lineCap: 'round',
      lineJoin: 'round'
    }).addTo(map);

    routeLineRef.current = polyline;

    // Fit bounds smoothly
    const bounds = L.latLngBounds(start, end);
    map.fitBounds(bounds, { padding: [80, 80] });
  };

  const handleResetLocation = () => {
    if (!mapInstanceRef.current) return;
    mapInstanceRef.current.flyTo([USER_LOCATION.lat, USER_LOCATION.lng], 14, {
      duration: 1
    });
  };

  const handleZoomIn = () => {
    if (!mapInstanceRef.current) return;
    mapInstanceRef.current.zoomIn();
  };

  const handleZoomOut = () => {
    if (!mapInstanceRef.current) return;
    mapInstanceRef.current.zoomOut();
  };

  const handleCopyPhone = (phone: string) => {
    navigator.clipboard?.writeText(phone);
    setCopiedPhone(true);
    setTimeout(() => setCopiedPhone(false), 2000);
  };

  const handleOpenOfficeDetails = (office: PishkhanOffice) => {
    setSelectedOffice(office);
    setShowFullDetailsModal(true);
  };

  const otherOffices = filteredOffices.filter(o => o.id !== selectedOffice?.id).slice(0, 5);

  const selectedOfficeStatus = selectedOffice ? getOfficeStatus(selectedOffice) : 'registered_online';

  return (
    <div className="relative w-full h-full min-h-[calc(100dvh-64px)] flex-1 bg-slate-100 overflow-hidden font-sans select-none" dir="rtl">
      
      {/* Map Canvas Container */}
      <div 
        ref={mapContainerRef} 
        className="w-full h-full absolute inset-0 z-0 cursor-grab active:cursor-grabbing"
      />

      {/* ========================================================================= */}
      {/* TOP FLOATING CONTROLS (Search + Filter Chips + Legend)                    */}
      {/* ========================================================================= */}
      <div className="absolute top-3 inset-x-2 sm:inset-x-4 z-20 space-y-2 pointer-events-none max-w-xl mx-auto">
        
        {/* Search Bar */}
        <div className="bg-white/95 backdrop-blur-md rounded-2xl shadow-lg border border-slate-200/80 p-2 flex items-center gap-2 pointer-events-auto">
          <div className="w-9 h-9 rounded-xl bg-emerald-50 text-[#1ea858] flex items-center justify-center shrink-0">
            <Search className="w-4 h-4" />
          </div>
          <div className="flex-1 flex items-center gap-2">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="جستجوی دفتر، کد، محله یا خدمات..."
              className="w-full bg-transparent text-xs font-bold text-slate-800 placeholder-slate-400 outline-none"
            />
            {searchQuery && (
              <button onClick={() => setSearchQuery('')} className="text-slate-400 hover:text-slate-600">
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          {/* Filter Toggle Button */}
          <button 
            onClick={() => setShowFilters(!showFilters)}
            title={showFilters ? 'بستن فیلترها' : 'نمایش فیلترها'}
            className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 cursor-pointer transition-colors relative ${
              showFilters 
                ? 'bg-emerald-600 text-white shadow-xs' 
                : filterChip !== 'all' 
                ? 'bg-emerald-100 text-emerald-800 ring-2 ring-emerald-500/30' 
                : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
            }`}
          >
            <SlidersHorizontal className="w-4 h-4" />
            {filterChip !== 'all' && !showFilters && (
              <span className="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            )}
          </button>

          {/* Legend Toggle Button */}
          <button 
            onClick={() => setShowLegend(!showLegend)}
            title="راهنمای دسته‌بندی دفاتر"
            className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 cursor-pointer transition-colors ${
              showLegend ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Info className="w-4 h-4" />
          </button>
        </div>

        {/* 3 Categories Filter Chips Bar (Collapsible) */}
        {showFilters && (
          <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 pointer-events-auto animate-in fade-in slide-in-from-top-2 duration-150">
            {/* Close Filter Row Button */}
            <button
              onClick={() => setShowFilters(false)}
              title="بستن نوار فیلتر"
              className="w-7 h-7 rounded-full bg-white/95 backdrop-blur-sm text-slate-400 hover:text-slate-700 hover:bg-slate-100 border border-slate-200/80 shadow-xs flex items-center justify-center shrink-0 cursor-pointer transition-all"
            >
              <X className="w-3.5 h-3.5" />
            </button>

            <button
              onClick={() => setFilterChip('all')}
              className={`px-3 py-1.5 rounded-full text-xs font-black transition-all cursor-pointer shrink-0 ${
                filterChip === 'all'
                  ? 'bg-[#1ea858] text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              همه دفاتر ({offices.length})
            </button>

            {/* 1. Registered & Online */}
            <button
              onClick={() => setFilterChip('registered_online')}
              className={`px-3 py-1.5 rounded-full text-xs font-black transition-all cursor-pointer shrink-0 flex items-center gap-1.5 ${
                filterChip === 'registered_online'
                  ? 'bg-emerald-600 text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              <span className="relative flex h-2 w-2">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
              </span>
              <span>آنلاین و عضو سامانه ({onlineCount})</span>
            </button>

            {/* 2. Registered & Offline */}
            <button
              onClick={() => setFilterChip('registered_offline')}
              className={`px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0 flex items-center gap-1.5 ${
                filterChip === 'registered_offline'
                  ? 'bg-slate-800 text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              <span className="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>
              <span>عضو سامانه (آفلاین) ({offlineCount})</span>
            </button>

            {/* 3. Unregistered */}
            <button
              onClick={() => setFilterChip('unregistered')}
              className={`px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0 flex items-center gap-1.5 ${
                filterChip === 'unregistered'
                  ? 'bg-amber-600 text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              <span className="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              <span>غیرعضو در سامانه ({unregisteredCount})</span>
            </button>

            <button
              onClick={() => setFilterChip('nearest')}
              className={`px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0 ${
                filterChip === 'nearest'
                  ? 'bg-[#1ea858] text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              نزدیک‌ترین
            </button>

            <button
              onClick={() => setFilterChip('top_rated')}
              className={`px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0 ${
                filterChip === 'top_rated'
                  ? 'bg-[#1ea858] text-white shadow-md'
                  : 'bg-white/95 backdrop-blur-sm text-slate-700 shadow-xs border border-slate-200/80 hover:bg-white'
              }`}
            >
              امتیاز برتر ⭐
            </button>
          </div>
        )}

        {/* Categories Explanatory Legend Bar (Collapsible) */}
        {showLegend && (
          <div className="bg-white/95 backdrop-blur-md rounded-2xl p-2.5 shadow-md border border-slate-200/80 text-[11px] pointer-events-auto space-y-1.5 animate-in fade-in zoom-in-95">
            <div className="flex items-center justify-between font-black text-slate-800 text-xs">
              <span className="flex items-center gap-1">
                <Compass className="w-3.5 h-3.5 text-emerald-600" />
                راهنمای ۳ وضعیت دفاتر پیشخوان روی نقشه:
              </span>
              <button 
                onClick={() => setShowLegend(false)}
                className="text-slate-400 hover:text-slate-600 text-[10px]"
              >
                بستن راهنما ✕
              </button>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-1.5 text-right pt-0.5">
              <div className="flex items-center gap-1.5 bg-emerald-50/80 border border-emerald-200 p-1.5 rounded-xl text-emerald-950">
                <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <span><strong>سبز:</strong> عضو سامانه و آنلاین (نوبت و ارجاع برخط فعال)</span>
              </div>
              <div className="flex items-center gap-1.5 bg-slate-100/90 border border-slate-200 p-1.5 rounded-xl text-slate-800">
                <span className="w-2.5 h-2.5 rounded-full bg-slate-500 shrink-0"></span>
                <span><strong>طوسی:</strong> عضو سامانه و آفلاین (نوبت فردا و مسیریابی)</span>
              </div>
              <div className="flex items-center gap-1.5 bg-amber-50/90 border border-amber-200 p-1.5 rounded-xl text-amber-950">
                <span className="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <span><strong>نارنجی:</strong> غیرعضو در سامانه (مراجعه سنتی و حضوری)</span>
              </div>
            </div>
          </div>
        )}

      </div>

      {/* ========================================================================= */}
      {/* FLOATING MAP CONTROLS STACK (Layers, GPS, Custom Zoom) - Right Side       */}
      {/* ========================================================================= */}
      <div className="absolute top-18 sm:top-20 right-2.5 sm:right-4 z-20 flex flex-col gap-2 pointer-events-auto">
        {/* GPS / Locate Me Button */}
        <button
          onClick={handleResetLocation}
          aria-label="موقعیت من"
          title="موقعیت من روی نقشه"
          className="w-9 h-9 sm:w-11 sm:h-11 bg-white text-[#1ea858] hover:bg-emerald-50 active:scale-95 rounded-xl sm:rounded-2xl shadow-lg border border-slate-200/80 flex items-center justify-center transition-all cursor-pointer"
        >
          <LocateFixed className="w-4 h-4 sm:w-5 sm:h-5 text-[#1ea858]" />
        </button>

        {/* Map Theme / Layer Switcher Button */}
        <div className="relative">
          <button
            onClick={() => setShowThemePicker(!showThemePicker)}
            title="تغییر لایه و سبک نقشه"
            className="w-9 h-9 sm:w-11 sm:h-11 bg-white hover:bg-slate-50 text-slate-700 active:scale-95 rounded-xl sm:rounded-2xl shadow-lg border border-slate-200/80 flex items-center justify-center transition-all cursor-pointer"
          >
            <Layers className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" />
          </button>

          {/* Theme Dropdown Menu */}
          {showThemePicker && (
            <div className="absolute top-0 right-11 sm:right-13 bg-white/95 backdrop-blur-md rounded-2xl p-2 shadow-2xl border border-slate-200/80 space-y-1 w-44 animate-in fade-in zoom-in-95 text-right z-30">
              <span className="text-[10px] font-black text-slate-400 px-2 block">سبک استریت مپ:</span>
              {(Object.keys(MAP_THEMES) as Array<keyof typeof MAP_THEMES>).map((key) => (
                <button
                  key={key}
                  onClick={() => {
                    setMapTheme(key);
                    setShowThemePicker(false);
                  }}
                  className={`w-full text-right px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-between ${
                    mapTheme === key ? 'bg-[#1ea858] text-white' : 'text-slate-700 hover:bg-slate-100'
                  }`}
                >
                  <span>{MAP_THEMES[key].name}</span>
                  {mapTheme === key && <CheckCircle2 className="w-3.5 h-3.5" />}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Zoom In & Out Capsule */}
        <div className="bg-white rounded-xl sm:rounded-2xl shadow-lg border border-slate-200/80 flex flex-col overflow-hidden divide-y divide-slate-100">
          <button
            onClick={handleZoomIn}
            title="بزرگ‌نمایی"
            className="w-9 h-8 sm:w-11 sm:h-9 flex items-center justify-center text-slate-700 hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer"
          >
            <Plus className="w-4 h-4" />
          </button>
          <button
            onClick={handleZoomOut}
            title="کوچک‌نمایی"
            className="w-9 h-8 sm:w-11 sm:h-9 flex items-center justify-center text-slate-700 hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer"
          >
            <Minus className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* ========================================================================= */}
      {/* FLOATING BOTTOM SHEET / DRAWER OVER MAP (MOBILE-OPTIMIZED)                */}
      {/* ========================================================================= */}
      {isDrawerExpanded ? (
        <div className="absolute bottom-[86px] sm:bottom-[92px] inset-x-2 sm:inset-x-4 z-20 max-w-xl mx-auto pointer-events-auto">
          <div className="bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.18)] border border-slate-200/90 p-3 sm:p-4 pt-2 pb-3.5 space-y-2.5 sm:space-y-3 max-h-[46dvh] sm:max-h-[380px] overflow-y-auto no-scrollbar transition-all animate-in slide-in-from-bottom-6 duration-200">
            
            {/* Drag / Collapse Handle Button */}
            <button
              onClick={() => setIsDrawerExpanded(false)}
              title="بستن و پایین بردن لیست دفاتر"
              className="w-full py-0.5 flex flex-col items-center justify-center group cursor-pointer"
            >
              <div className="w-10 h-1.5 bg-slate-300 group-hover:bg-slate-400 rounded-full transition-colors" />
            </button>

            {/* Header: Office Count & Sort Indicator & Minimize Button */}
            <div className="flex items-center justify-between text-xs px-1">
              <div className="flex items-center gap-1.5">
                <h3 className="font-black text-xs sm:text-sm text-slate-900">
                  {filteredOffices.length} دفتر در این محدوده
                </h3>
                <span className="text-[10px] sm:text-[11px] text-slate-500 font-medium hidden sm:inline-block">
                  ({
                    filterChip === 'registered_online' ? '🟢 آنلاین' : 
                    filterChip === 'registered_offline' ? '⚪ آفلاین' : 
                    filterChip === 'unregistered' ? '🟠 غیرعضو' : 
                    filterChip === 'top_rated' ? 'امتیاز برتر' : 'همه'
                  })
                </span>
              </div>
              
              <button
                onClick={() => setIsDrawerExpanded(false)}
                title="پایین بردن لیست"
                className="flex items-center gap-1 text-[11px] font-bold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
              >
                <span>پایین بردن</span>
                <ChevronDown className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* ACTIVE SELECTED OFFICE CARD */}
            {selectedOffice && (
              <div 
                onClick={() => handleOpenOfficeDetails(selectedOffice)}
                className={`rounded-2xl p-2.5 sm:p-3.5 shadow-xs space-y-2.5 transition-all cursor-pointer group ${
                  selectedOfficeStatus === 'registered_online'
                    ? 'border-2 border-[#1ea858] bg-emerald-50/30 hover:bg-emerald-50/50'
                    : selectedOfficeStatus === 'registered_offline'
                    ? 'border-2 border-slate-300 bg-slate-50/70 hover:bg-slate-100/70'
                    : 'border-2 border-dashed border-amber-300 bg-amber-50/40 hover:bg-amber-50/60'
                }`}
              >
                {/* Top Row: Store Icon + Title & Distance Info (Right) + Status Badge (Left) */}
                <div className="flex items-start justify-between gap-2 sm:gap-3">
                  
                  {/* Right Side: Store Icon + Title + Metrics */}
                  <div className="flex items-center gap-2.5 sm:gap-3 flex-1 min-w-0">
                    {/* Store Icon Square */}
                    <div className={`w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0 shadow-sm text-white ${
                      selectedOfficeStatus === 'registered_online'
                        ? 'bg-gradient-to-br from-emerald-500 to-[#1ea858]'
                        : selectedOfficeStatus === 'registered_offline'
                        ? 'bg-gradient-to-br from-slate-600 to-slate-700'
                        : 'bg-gradient-to-br from-amber-500 to-orange-600'
                    }`}>
                      {selectedOfficeStatus === 'registered_online' ? (
                        <Store className="w-4 h-4 sm:w-5 sm:h-5" />
                      ) : selectedOfficeStatus === 'registered_offline' ? (
                        <ShieldCheck className="w-4 h-4 sm:w-5 sm:h-5" />
                      ) : (
                        <Building className="w-4 h-4 sm:w-5 sm:h-5" />
                      )}
                    </div>

                    <div className="text-right min-w-0 flex-1">
                      <h4 className={`font-black text-xs sm:text-sm truncate transition-colors ${
                        selectedOfficeStatus === 'registered_online'
                          ? 'text-slate-900 group-hover:text-emerald-800' 
                          : selectedOfficeStatus === 'registered_offline'
                          ? 'text-slate-800 group-hover:text-slate-950'
                          : 'text-amber-950 group-hover:text-orange-950'
                      }`}>
                        {selectedOffice.name}
                      </h4>
                      <div className="flex items-center gap-1.5 text-[11px] sm:text-xs text-slate-600 font-bold mt-0.5 flex-wrap">
                        <span className="flex items-center gap-0.5 text-amber-600 font-bold">
                          <Star className="w-3 h-3 fill-amber-400 text-amber-400 inline" />
                          {selectedOffice.rating}
                        </span>
                        <span className="text-slate-300">•</span>
                        <span className="text-slate-700 flex items-center gap-0.5">
                          {selectedOffice.distanceKm} کیلومتر
                        </span>
                        <span className="text-slate-300">•</span>
                        <span className="text-slate-500">
                          {Math.round(selectedOffice.distanceKm * 6 + 4)} دقیقه
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Left Side: Status Tag */}
                  <div className="flex items-center shrink-0">
                    {selectedOfficeStatus === 'registered_online' && (
                      <span className="bg-emerald-100 text-emerald-800 text-[9px] sm:text-[10px] font-black px-2 sm:px-2.5 py-0.5 rounded-full flex items-center gap-1 border border-emerald-200">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>آنلاین</span>
                      </span>
                    )}

                    {selectedOfficeStatus === 'registered_offline' && (
                      <span className="bg-slate-200 text-slate-700 text-[9px] sm:text-[10px] font-black px-2 sm:px-2.5 py-0.5 rounded-full flex items-center gap-1">
                        <span className="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>آفلاین</span>
                      </span>
                    )}

                    {selectedOfficeStatus === 'unregistered' && (
                      <span className="bg-amber-100 text-amber-900 text-[9px] sm:text-[10px] font-black px-2 sm:px-2.5 py-0.5 rounded-full flex items-center gap-1 border border-amber-300">
                        <span className="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                        <span>غیرعضو</span>
                      </span>
                    )}
                  </div>

                </div>

                {/* Status Notice Banner if needed */}
                {selectedOfficeStatus === 'registered_offline' && (
                  <div className="bg-slate-100/90 border border-slate-200 rounded-xl p-2 text-[10px] sm:text-[11px] text-slate-700 flex items-center gap-1.5 text-right">
                    <Moon className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                    <span>دفتر در ساعات استراحت است (امکان رزرو نوبت فردا فعال است).</span>
                  </div>
                )}

                {selectedOfficeStatus === 'unregistered' && (
                  <div className="bg-amber-50 border border-amber-200 rounded-xl p-2 text-[10px] sm:text-[11px] text-amber-900 flex items-center gap-1.5 text-right">
                    <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                    <span>اطلاعات این دفتر دایرکتوری بوده و مراجعات به صورت حضوری است.</span>
                  </div>
                )}

                {/* Bottom Action Buttons */}
                <div className="grid grid-cols-2 gap-2 pt-0.5">
                  {/* Green Filled Button: مسیریابی */}
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleStartRouting();
                    }}
                    className="bg-[#1ea858] hover:bg-emerald-600 active:scale-95 text-white font-black h-9 sm:h-10 px-2 rounded-xl flex items-center justify-center gap-1 text-xs shadow-xs transition-all cursor-pointer"
                  >
                    <Navigation className="w-3.5 h-3.5" />
                    <span>مسیریابی</span>
                  </button>

                  {/* Outlined Button: مشاهده مشخصات و خدمات */}
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowFullDetailsModal(true);
                    }}
                    className="border border-slate-300 hover:border-[#1ea858] text-slate-800 hover:text-[#1ea858] hover:bg-emerald-50 active:scale-95 font-black h-9 sm:h-10 px-2 rounded-xl flex items-center justify-center gap-1 text-xs bg-white transition-all cursor-pointer shadow-xs"
                  >
                    <Info className="w-3.5 h-3.5" />
                    <span>مشاهده و خدمات</span>
                  </button>
                </div>
              </div>
            )}

            {/* SECONDARY OFFICES LIST */}
            <div className="space-y-1.5 pt-0.5">
              <div className="text-[10px] font-bold text-slate-400 px-1">سایر دفاتر نزدیک:</div>
              {otherOffices.map((office) => {
                const offStatus = getOfficeStatus(office);
                return (
                  <div
                    key={office.id}
                    onClick={() => {
                      setSelectedOffice(office);
                      setShowFullDetailsModal(true);
                    }}
                    className="p-2 sm:p-2.5 rounded-xl sm:rounded-2xl bg-slate-50/80 hover:bg-white border border-slate-100 flex items-center justify-between gap-2 cursor-pointer transition-all active:bg-slate-100 group"
                  >
                    {/* Right Side: Icon + Title and details */}
                    <div className="flex items-center gap-2 flex-1 min-w-0">
                      <div className={`w-8 h-8 rounded-lg sm:rounded-xl shadow-xs flex items-center justify-center shrink-0 transition-colors border ${
                        offStatus === 'registered_online' 
                          ? 'bg-white text-emerald-600 group-hover:bg-emerald-50 border-emerald-100' 
                          : offStatus === 'registered_offline'
                          ? 'bg-slate-100 text-slate-400 border-slate-200'
                          : 'bg-amber-50 text-amber-600 border-amber-200'
                      }`}>
                        {offStatus === 'registered_online' ? (
                          <Store className="w-3.5 h-3.5" />
                        ) : offStatus === 'registered_offline' ? (
                          <ShieldCheck className="w-3.5 h-3.5" />
                        ) : (
                          <Building className="w-3.5 h-3.5" />
                        )}
                      </div>

                      <div className="text-right min-w-0 flex-1">
                        <div className="flex items-center gap-1.5">
                          <h5 className="font-bold text-xs text-slate-900 group-hover:text-emerald-700 transition-colors truncate">
                            {office.name}
                          </h5>
                          <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded shrink-0 ${
                            offStatus === 'registered_online' 
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                              : offStatus === 'registered_offline'
                              ? 'bg-slate-200 text-slate-600'
                              : 'bg-amber-100 text-amber-900 border border-amber-300'
                          }`}>
                            {offStatus === 'registered_online' ? '🟢 آنلاین' : offStatus === 'registered_offline' ? '⚪ آفلاین' : '🟠 غیرعضو'}
                          </span>
                        </div>
                        <div className="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                          <span className="text-amber-500 font-bold">{office.rating} ★</span>
                          <span>•</span>
                          <span>{office.distanceKm} کیلومتر</span>
                          <span>•</span>
                          <span>کد {office.code}</span>
                        </div>
                      </div>
                    </div>

                    {/* Left Side: Action text + arrow */}
                    <div className="flex items-center gap-0.5 text-slate-400 group-hover:text-emerald-600 transition-colors shrink-0">
                      <span className="text-[10px] font-bold">جزئیات</span>
                      <ChevronLeft className="w-3.5 h-3.5" />
                    </div>
                  </div>
                );
              })}
            </div>

          </div>
        </div>
      ) : (
        <div className="absolute bottom-[86px] sm:bottom-[92px] inset-x-2.5 sm:inset-x-4 z-20 max-w-xl mx-auto pointer-events-auto">
          <div 
            onClick={() => setIsDrawerExpanded(true)}
            className="bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.16)] border border-slate-200/90 p-2.5 sm:p-3 px-3 sm:px-4 flex items-center justify-between cursor-pointer hover:bg-white transition-all animate-in slide-in-from-bottom-4 duration-200 group active:scale-[0.99]"
          >
            <div className="flex items-center gap-2.5 min-w-0">
              <div className="w-9 h-9 rounded-xl bg-emerald-50 text-[#1ea858] border border-emerald-200/60 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                <Store className="w-4 h-4" />
              </div>
              <div className="text-right min-w-0">
                <h4 className="font-black text-xs sm:text-sm text-slate-900 flex items-center gap-1.5 truncate">
                  <span>{filteredOffices.length} دفتر در این محدوده</span>
                  {selectedOffice && (
                    <span className="text-[9px] sm:text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-full font-bold truncate">
                      {selectedOffice.name}
                    </span>
                  )}
                </h4>
                <p className="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">برای مشاهده لیست دفاتر و جزئیات لمس کنید</p>
              </div>
            </div>

            <button
              onClick={(e) => {
                e.stopPropagation();
                setIsDrawerExpanded(true);
              }}
              className="flex items-center gap-1 text-[11px] sm:text-xs font-black text-[#1ea858] bg-emerald-50 hover:bg-emerald-100 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl border border-emerald-200/80 transition-colors shadow-xs shrink-0 cursor-pointer"
            >
              <span>نمایش لیست</span>
              <ChevronUp className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* FULL OFFICE DETAILS MODAL OPENED DIRECTLY ON TOP OF MAP (PORTALED)        */}
      {/* ========================================================================= */}
      {showFullDetailsModal && selectedOffice && typeof document !== 'undefined' && createPortal(
        <div 
          className="fixed inset-0 z-[100] bg-slate-950/70 backdrop-blur-md flex items-end sm:items-center justify-center p-0 sm:p-4 animate-in fade-in duration-200"
          onClick={() => setShowFullDetailsModal(false)}
        >
          <div 
            onClick={(e) => e.stopPropagation()}
            className="bg-white w-full max-w-2xl rounded-t-[2.2rem] sm:rounded-3xl p-4 sm:p-6 pb-6 sm:pb-6 shadow-2xl border border-slate-100 space-y-3.5 sm:space-y-4 max-h-[85dvh] sm:max-h-[88vh] overflow-y-auto no-scrollbar animate-in slide-in-from-bottom-8 duration-250 flex flex-col"
            dir="rtl"
          >
            {/* Modal Header */}
            <div className="flex items-start justify-between gap-2.5 pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
                <div className={`w-11 h-11 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-md shrink-0 text-white ${
                  selectedOfficeStatus === 'registered_online'
                    ? 'bg-gradient-to-br from-emerald-500 to-[#1ea858] shadow-emerald-600/20'
                    : selectedOfficeStatus === 'registered_offline'
                    ? 'bg-gradient-to-br from-slate-600 to-slate-800'
                    : 'bg-gradient-to-br from-amber-500 to-orange-600 shadow-amber-600/20'
                }`}>
                  {selectedOfficeStatus === 'registered_online' ? (
                    <Store className="w-5 h-5 sm:w-7 sm:h-7" />
                  ) : selectedOfficeStatus === 'registered_offline' ? (
                    <ShieldCheck className="w-5 h-5 sm:w-7 sm:h-7" />
                  ) : (
                    <Building className="w-5 h-5 sm:w-7 sm:h-7" />
                  )}
                </div>
                <div className="text-right min-w-0 flex-1">
                  <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <h3 className="font-black text-sm sm:text-base text-slate-900 truncate">{selectedOffice.name}</h3>
                    <span className="bg-emerald-100 text-emerald-800 text-[10px] sm:text-xs font-black px-2 py-0.5 rounded-full shrink-0">
                      کد {selectedOffice.code}
                    </span>
                    <span className={`text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full shrink-0 ${
                      selectedOfficeStatus === 'registered_online' 
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                        : selectedOfficeStatus === 'registered_offline'
                        ? 'bg-slate-100 text-slate-600 border border-slate-200'
                        : 'bg-amber-50 text-amber-900 border border-amber-300'
                    }`}>
                      {selectedOfficeStatus === 'registered_online' ? '🟢 آنلاین' : selectedOfficeStatus === 'registered_offline' ? '⚪ آفلاین' : '🟠 غیرعضو'}
                    </span>
                  </div>
                  <p className="text-[11px] sm:text-xs text-slate-500 mt-0.5 sm:mt-1 flex items-center gap-1">
                    <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <span className="truncate">{selectedOffice.address}</span>
                  </p>
                </div>
              </div>

              <button
                onClick={() => setShowFullDetailsModal(false)}
                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 cursor-pointer transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Alert Box according to Status */}
            {selectedOfficeStatus === 'registered_offline' && (
              <div className="bg-slate-50 border border-slate-200 rounded-xl sm:rounded-2xl p-3 text-xs flex items-start gap-2 text-slate-800">
                <Moon className="w-4 h-4 text-slate-600 shrink-0 mt-0.5" />
                <div className="text-right leading-relaxed">
                  <strong className="block font-black text-slate-900 mb-0.5 text-xs">
                    دفتر عضو پلتفرم است اما هم‌اکنون در ساعات تعطیلی قرار دارد
                  </strong>
                  <p className="text-[11px] text-slate-600">
                    امکان ارجاع برخط در این ساعت فعال نیست. شما می‌توانید نوبت حضوری برای روز کاری آینده رزرو نمایید یا از مسیریابی به آدرس دفتر استفاده کنید.
                  </p>
                </div>
              </div>
            )}

            {selectedOfficeStatus === 'unregistered' && (
              <div className="bg-amber-50 border border-amber-200 rounded-xl sm:rounded-2xl p-3 text-xs flex items-start gap-2 text-amber-900">
                <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                <div className="text-right leading-relaxed">
                  <strong className="block font-black text-amber-950 mb-0.5 text-xs">
                    این دفتر پیشخوان در سامانه هوشمند ثبت‌نام نکرده است
                  </strong>
                  <p className="text-[11px] text-amber-800">
                    اطلاعات این دفتر صرفاً از دایرکتوری پایگاه کشوری نمایش داده می‌شود. هیچ‌گونه درگاه دریافت الکترونیک پرونده، امضای دیجیتال یا سیستم نوبت‌دهی آنلاین برای این دفتر فعال نیست و مراجعات صرفاً حضوری و سنتی است.
                  </p>
                </div>
              </div>
            )}

            {/* Quick Metrics Bar */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2 text-xs text-right">
              <div className="bg-slate-50 p-2 sm:p-2.5 rounded-xl sm:rounded-2xl border border-slate-100">
                <span className="text-slate-400 block text-[10px]">فاصله تا شما:</span>
                <span className="font-extrabold text-xs sm:text-sm text-slate-800">{selectedOffice.distanceKm} ک.م ({Math.round(selectedOffice.distanceKm * 6 + 4)} د)</span>
              </div>

              <div className="bg-slate-50 p-2 sm:p-2.5 rounded-xl sm:rounded-2xl border border-slate-100">
                <span className="text-slate-400 block text-[10px]">وضعیت اتصال:</span>
                <span className={`font-extrabold text-xs sm:text-sm ${
                  selectedOfficeStatus === 'registered_online' 
                    ? 'text-emerald-700' 
                    : selectedOfficeStatus === 'registered_offline'
                    ? 'text-slate-600'
                    : 'text-amber-700'
                }`}>
                  {selectedOfficeStatus === 'registered_online' ? `${selectedOffice.currentWaitingQueue} در صف` : selectedOfficeStatus === 'registered_offline' ? 'آفلاین' : 'غیرعضو'}
                </span>
              </div>

              <div className="bg-slate-50 p-2 sm:p-2.5 rounded-xl sm:rounded-2xl border border-slate-100">
                <span className="text-slate-400 block text-[10px]">باجه‌های فعال:</span>
                <span className="font-extrabold text-xs sm:text-sm text-slate-800">{selectedOffice.activeCounters} باجه</span>
              </div>

              <div className="bg-slate-50 p-2 sm:p-2.5 rounded-xl sm:rounded-2xl border border-slate-100">
                <span className="text-slate-400 block text-[10px]">ساعت کاری:</span>
                <span className="font-extrabold text-xs sm:text-sm text-slate-800 truncate">{selectedOffice.workingHours}</span>
              </div>
            </div>

            {/* Medals & Badges */}
            <div className="space-y-1 text-right">
              <span className="text-[11px] sm:text-xs font-extrabold text-slate-800 flex items-center gap-1">
                <Award className="w-3.5 h-3.5 text-amber-500" />
                مدال‌ها و وضعیت در پلتفرم:
              </span>
              <div className="flex flex-wrap gap-1 sm:gap-1.5">
                {selectedOffice.medals.map((medal, i) => (
                  <span 
                    key={i}
                    className="bg-amber-50 text-amber-900 border border-amber-200 text-[10px] sm:text-[11px] font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg sm:rounded-xl flex items-center gap-1"
                  >
                    <Sparkles className="w-3 h-3 text-amber-500" />
                    {medal}
                  </span>
                ))}
              </div>
            </div>

            {/* Specialties */}
            <div className="space-y-1 text-right">
              <span className="text-[11px] sm:text-xs font-extrabold text-slate-800">باجه‌های تخصصی:</span>
              <div className="flex flex-wrap gap-1 sm:gap-1.5">
                {selectedOffice.specialties.map((spec, i) => (
                  <span 
                    key={i}
                    className="bg-indigo-50 text-indigo-900 border border-indigo-200 text-[10px] sm:text-[11px] font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg sm:rounded-xl flex items-center gap-1"
                  >
                    <CheckCircle2 className="w-3 h-3 text-indigo-600" />
                    {spec}
                  </span>
                ))}
              </div>
            </div>

            {/* Tab Navigation (اطلاعات تماس / خدمات / نظرات) */}
            <div className="border-t border-slate-100 pt-2.5 sm:pt-3">
              <div className="flex items-center gap-1.5 sm:gap-2 mb-2.5">
                <button
                  onClick={() => setModalTab('info')}
                  className={`flex-1 text-[11px] sm:text-xs font-extrabold py-1.5 sm:py-2 rounded-xl transition-all cursor-pointer ${
                    modalTab === 'info' ? 'bg-[#1ea858] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  اطلاعات تماس
                </button>
                <button
                  onClick={() => setModalTab('services')}
                  className={`flex-1 text-[11px] sm:text-xs font-extrabold py-1.5 sm:py-2 rounded-xl transition-all cursor-pointer ${
                    modalTab === 'services' ? 'bg-[#1ea858] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  فهرست خدمات
                </button>
                <button
                  onClick={() => setModalTab('reviews')}
                  className={`flex-1 text-[11px] sm:text-xs font-extrabold py-1.5 sm:py-2 rounded-xl transition-all cursor-pointer ${
                    modalTab === 'reviews' ? 'bg-[#1ea858] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  نظرات ({selectedOffice.reviewCount})
                </button>
              </div>

              {modalTab === 'info' && (
                <div className="bg-slate-50 p-2.5 sm:p-3 rounded-xl sm:rounded-2xl text-[11px] sm:text-xs space-y-2 text-slate-700 text-right">
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500">مدیریت دفتر:</span>
                    <span className="font-bold text-slate-900">{selectedOffice.managerName}</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500">تلفن تماس:</span>
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-slate-900 font-mono">{selectedOffice.phone}</span>
                      <button 
                        onClick={() => handleCopyPhone(selectedOffice.phone)}
                        className="text-emerald-700 hover:text-emerald-800 text-[10px] font-bold cursor-pointer"
                      >
                        {copiedPhone ? 'کپی شد!' : 'کپی شماره'}
                      </button>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500">منطقه شهرداری:</span>
                    <span className="font-bold text-slate-900">{selectedOffice.region}</span>
                  </div>
                </div>
              )}

              {modalTab === 'services' && (
                <div className="space-y-1.5 max-h-36 sm:max-h-40 overflow-y-auto no-scrollbar">
                  {services
                    .filter(s => selectedOffice.supportedCategoryIds.includes(s.categoryId))
                    .map(s => (
                      <div key={s.id} className="p-2 sm:p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                        <span className="font-bold text-slate-800 text-[11px] sm:text-xs">{s.title}</span>
                        <span className="text-[9px] sm:text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-lg shrink-0">
                          {s.estimatedDays}
                        </span>
                      </div>
                    ))}
                </div>
              )}

              {modalTab === 'reviews' && (
                <div className="space-y-1.5 max-h-36 sm:max-h-40 overflow-y-auto no-scrollbar">
                  <div className="p-2 sm:p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1 text-right">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-slate-900 text-[11px] sm:text-xs">مهدی کریمی</span>
                      <span className="text-amber-500 font-bold text-xs">۵ ⭐</span>
                    </div>
                    <p className="text-[10px] sm:text-[11px] text-slate-600 leading-relaxed">
                      بسیار سریع و منظم. بدون معطلی کار شناسنامه المثنی انجام شد.
                    </p>
                  </div>
                  <div className="p-2 sm:p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1 text-right">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-slate-900 text-[11px] sm:text-xs">سارا باقری</span>
                      <span className="text-amber-500 font-bold text-xs">۵ ⭐</span>
                    </div>
                    <p className="text-[10px] sm:text-[11px] text-slate-600 leading-relaxed">
                      نوبت آنلاین دقیقاً سر ساعت فراخوانی شد. برخورد پرسنل عالی.
                    </p>
                  </div>
                </div>
              )}
            </div>

            {/* Action Buttons in Modal */}
            <div className="space-y-2 pt-1 sm:pt-2">
              {selectedOfficeStatus === 'registered_online' ? (
                /* Online Office: Full Actions Available */
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <button
                    onClick={() => {
                      setShowFullDetailsModal(false);
                      onDirectAssign(selectedOffice);
                    }}
                    className="w-full bg-[#1ea858] hover:bg-emerald-600 active:scale-98 text-white font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    <Zap className="w-4 h-4 text-amber-300" />
                    <span>ارسال مستقیم پرونده به این دفتر</span>
                  </button>

                  <button
                    onClick={() => {
                      setShowFullDetailsModal(false);
                      onBookAppointment(selectedOffice);
                    }}
                    className="w-full bg-slate-100 hover:bg-slate-200 active:scale-98 text-slate-800 font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    <Calendar className="w-4 h-4 text-indigo-600" />
                    <span>رزرو نوبت حضوری</span>
                  </button>
                </div>
              ) : selectedOfficeStatus === 'registered_offline' ? (
                /* Offline Office: Next-Day Appointment & Navigation */
                <div className="space-y-2">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                      disabled
                      title="دفتر در حال حاضر آفلاین است"
                      className="w-full bg-slate-100 border border-slate-200 text-slate-400 font-bold py-2.5 sm:py-3 rounded-xl sm:rounded-2xl flex items-center justify-center gap-1.5 text-xs cursor-not-allowed opacity-75"
                    >
                      <Zap className="w-4 h-4 text-slate-400" />
                      <span>عدم ارجاع آنی (دفتر آفلاین)</span>
                    </button>

                    <button
                      onClick={() => {
                        setShowFullDetailsModal(false);
                        onBookAppointment(selectedOffice);
                      }}
                      className="w-full bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 active:scale-98 text-indigo-900 font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                    >
                      <Calendar className="w-4 h-4 text-indigo-600" />
                      <span>رزرو نوبت برای فردا</span>
                    </button>
                  </div>

                  <button
                    onClick={() => {
                      setShowFullDetailsModal(false);
                      handleStartRouting();
                    }}
                    className="w-full bg-[#1ea858] hover:bg-emerald-600 active:scale-98 text-white font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    <Navigation className="w-4 h-4" />
                    <span>مسیریابی حضوری به نشانی این دفتر</span>
                  </button>
                </div>
              ) : (
                /* Unregistered Office: Traditional Call & Navigation */
                <div className="space-y-2">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a
                      href={`tel:${selectedOffice.phone}`}
                      className="w-full bg-amber-500 hover:bg-amber-600 active:scale-98 text-white font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl shadow-md flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                    >
                      <Phone className="w-4 h-4" />
                      <span>تماس تلفنی با دفتر</span>
                    </a>

                    <button
                      onClick={() => {
                        setShowFullDetailsModal(false);
                        handleStartRouting();
                      }}
                      className="w-full bg-[#1ea858] hover:bg-emerald-600 active:scale-98 text-white font-black py-2.5 sm:py-3 rounded-xl sm:rounded-2xl shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 text-xs sm:text-sm transition-all cursor-pointer"
                    >
                      <Navigation className="w-4 h-4" />
                      <span>مسیریابی به محل دفتر</span>
                    </button>
                  </div>

                  <p className="text-[10px] text-slate-400 text-center font-bold">
                    جهت ارسال آنلاین مدارک و پرداخت اینترنتی، یکی از دفاتر با نشان سبز را انتخاب نمایید.
                  </p>
                </div>
              )}
            </div>

          </div>
        </div>,
        document.body
      )}

    </div>
  );
};
