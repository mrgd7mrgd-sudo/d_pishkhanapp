import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import type { OfficeItem, Coordinates, MapTheme } from '../types';
import { MAP_THEMES } from '../constants';
import {
  createOfficeMarkerIcon,
  createUserLocationIcon,
} from './CustomOfficeMarkers';

interface LeafletMapContainerProps {
  center: Coordinates;
  userCoords: Coordinates | null;
  offices: OfficeItem[];
  selectedOffice: OfficeItem | null;
  theme: MapTheme;
  onSelectOffice: (office: OfficeItem) => void;
}

const updateTileLayer = (
  map: L.Map,
  layerRef: React.MutableRefObject<L.TileLayer | null>,
  theme: MapTheme,
): void => {
  if (layerRef.current) map.removeLayer(layerRef.current);
  const config = MAP_THEMES[theme];
  const newLayer = L.tileLayer(config.tileUrl, { attribution: config.attribution, maxZoom: 19 });
  newLayer.addTo(map);
  layerRef.current = newLayer;
};

const useOfficeMarkers = (
  map: L.Map | null,
  offices: OfficeItem[],
  selectedId: string | undefined,
  onSelectOffice: (office: OfficeItem) => void,
): void => {
  const groupRef = useRef<L.LayerGroup | null>(null);
  useEffect(() => {
    if (!map) return;
    if (groupRef.current) map.removeLayer(groupRef.current);
    const group = L.layerGroup();
    offices.forEach((office) => {
      const isSelected = office.id === selectedId;
      const icon = createOfficeMarkerIcon(office.membership_status, isSelected);
      const marker = L.marker([office.coords.lat, office.coords.lng], { icon });
      marker.on('click', () => onSelectOffice(office));
      group.addLayer(marker);
    });
    group.addTo(map);
    groupRef.current = group;
  }, [map, offices, selectedId, onSelectOffice]);
};

const useUserMarker = (map: L.Map | null, userCoords: Coordinates | null): void => {
  const markerRef = useRef<L.Marker | null>(null);
  useEffect(() => {
    if (!map) return;
    if (markerRef.current) {
      map.removeLayer(markerRef.current);
      markerRef.current = null;
    }
    if (userCoords) {
      const marker = L.marker([userCoords.lat, userCoords.lng], { icon: createUserLocationIcon() });
      marker.addTo(map);
      markerRef.current = marker;
    }
  }, [map, userCoords]);
};

export const LeafletMapContainer: React.FC<LeafletMapContainerProps> = ({
  center,
  userCoords,
  offices,
  selectedOffice,
  theme,
  onSelectOffice,
}) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<L.Map | null>(null);
  const tileLayerRef = useRef<L.TileLayer | null>(null);

  useEffect(() => {
    if (!containerRef.current || mapRef.current) return;
    const map = L.map(containerRef.current, { center: [center.lat, center.lng], zoom: 14, zoomControl: false });
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    mapRef.current = map;
    updateTileLayer(map, tileLayerRef, theme);
    return () => {
      map.remove();
      mapRef.current = null;
    };
  }, []);

  useEffect(() => {
    if (mapRef.current) updateTileLayer(mapRef.current, tileLayerRef, theme);
  }, [theme]);

  useEffect(() => {
    if (mapRef.current) mapRef.current.setView([center.lat, center.lng], mapRef.current.getZoom());
  }, [center.lat, center.lng]);

  useOfficeMarkers(mapRef.current, offices, selectedOffice?.id, onSelectOffice);
  useUserMarker(mapRef.current, userCoords);

  return (
    <div
      ref={containerRef}
      className="w-full h-full min-h-[350px] relative z-0"
      role="region"
      aria-label="Interactive Map"
      data-testid="leaflet-map-canvas"
    />
  );
};
