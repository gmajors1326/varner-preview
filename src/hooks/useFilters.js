import { useState, useMemo } from 'react';

const DEFAULT_FILTERS = {
  status: [], categories: [], makes: [], models: [],
  yearMin: '', yearMax: '', priceMin: '', priceMax: '',
  conditions: [], stockSearch: '', vinSearch: ''
};

export function useFilters(inventoryList, isPublicMode) {
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilters, setActiveFilters] = useState(DEFAULT_FILTERS);
  const [showFilterPanel, setShowFilterPanel] = useState(false);

  const handleFilterChange = (key, value) => setActiveFilters(prev => ({ ...prev, [key]: value }));

  const handleClearFilters = () => {
    setActiveFilters(DEFAULT_FILTERS);
    setSearchQuery('');
  };

  const filteredInventory = useMemo(() => {
    return inventoryList.filter(item => {
      const hit = (q) => !q || (
        item.stock?.toLowerCase().includes(q) || item.make?.toLowerCase().includes(q) ||
        item.model?.toLowerCase().includes(q) || item.year?.toLowerCase().includes(q) ||
        item.category?.toLowerCase().includes(q) || item.condition?.toLowerCase().includes(q)
      );
      if (isPublicMode && item.showOnWebsite === false) return false;
      if (!hit(searchQuery?.toLowerCase())) return false;
      if (activeFilters.status.length && !activeFilters.status.includes(item.status)) return false;
      if (activeFilters.categories.length && !activeFilters.categories.includes(item.category)) return false;
      if (activeFilters.makes.length && !activeFilters.makes.includes(item.make)) return false;
      if (activeFilters.models.length && !activeFilters.models.includes(item.model)) return false;
      if (activeFilters.yearMin && parseInt(item.year) < parseInt(activeFilters.yearMin)) return false;
      if (activeFilters.yearMax && parseInt(item.year) > parseInt(activeFilters.yearMax)) return false;
      if (activeFilters.priceMin && parseInt(item.price || 0) < parseInt(activeFilters.priceMin)) return false;
      if (activeFilters.priceMax && parseInt(item.price || 0) > parseInt(activeFilters.priceMax)) return false;
      if (activeFilters.conditions.length && !activeFilters.conditions.includes(item.condition)) return false;
      if (activeFilters.stockSearch && !item.stock?.toLowerCase().includes(activeFilters.stockSearch.toLowerCase())) return false;
      if (activeFilters.vinSearch && !item.vin?.toLowerCase().includes(activeFilters.vinSearch.toLowerCase())) return false;
      return true;
    });
  }, [inventoryList, isPublicMode, searchQuery, activeFilters]);

  return {
    searchQuery, setSearchQuery,
    activeFilters, showFilterPanel, setShowFilterPanel,
    handleFilterChange, handleClearFilters,
    filteredInventory,
  };
}
