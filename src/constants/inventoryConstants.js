export const DEFAULT_EMPTY_UNIT = {
  id: null,
  title: '', year: '', make: '', model: '', stockNumber: '', condition: 'New',
  price: '', callForPrice: false, vin: '', stockStatus: 'Draft', category: 'Compact Tractors',
  color: '', length: '', meter: '', meterType: 'Hours', intakeDate: '', description: '',
  featured: false, showOnWebsite: true, images: [], image_ids: [], attachments: [],
  sellerInfo: '<p>Call or stop by to see it in person</p><p>Varner Equipment</p><p>1375 Hwy 50</p><p>Delta, CO 81416</p><p>(970) 874-0612</p>',
  hasAttachments: false, attachmentDetails: '', engineHorsepower: '', drive: '',
};

export const COLOR_OPTIONS = [
  'Black', 'Red', 'Green', 'Green/Yellow', 'Brown', 
  'Orange', 'Blue', 'Yellow', 'Gray', 'Silver', 'White', 
  'Red/White', 'Blue/Black', 'Orange/Black', 
  'Black/Gray', 'Gray/Black', 'Red/Black', 'Silver/Black', 'Yellow/Black'
];

export const STATUS_OPTIONS = ['In Stock', 'Pending Sale', 'Sold', 'Draft'];

export const CONDITION_OPTIONS = ['New', 'Used'];

export const METER_TYPE_OPTIONS = ['Hours', 'Miles', 'Acres'];

export function getCategoryLabel(cat) {
  if (!cat) return 'unit';
  const c = String(cat).toLowerCase();
  if (c.includes('tractor')) return 'tractor';
  if (c.includes('trailer')) return 'trailer';
  if (c.includes('implement') || c.includes('attachment')) return 'attachment';
  return 'unit';
}
