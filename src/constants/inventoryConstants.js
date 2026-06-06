export const DEFAULT_EMPTY_UNIT = {
  id: null,
  title: '', year: '', make: '', model: '', stockNumber: '', condition: 'New',
  price: '', callForPrice: false, vin: '', stockStatus: 'Draft', 
  category: 'Tractors', subcategory: '', sub_subcategory: '',
  color: '', length: '', meter: '', meterType: 'Hours', intakeDate: '', description: '',
  featured: false, showOnWebsite: true, images: [], image_ids: [], attachments: [],
  sellerInfo: '<p>Call or stop by to see it in person</p><p>Varner Equipment</p><p>1375 Hwy 50</p><p>Delta, CO 81416</p><p>(970) 874-0612</p>',
  hasAttachments: false, attachmentDetails: '', drive: '',
};

export const CATEGORY_TREE = {
  "Utility Vehicles": {
    "Utility": []
  },
  "Tractors": {
    "175 HP to 299 HP": [],
    "100 HP to 174 HP": [],
    "40 HP to 99 HP": [],
    "Less than 40 HP": []
  },
  "Planting Equipment": {
    "Other": []
  },
  "Tillage Equipment": {
    "Chisel Plows": [],
    "Disks": [],
    "Plows": [],
    "Rippers": [],
    "Rotary Tillage": [],
    "Row Crop Cultivators": [],
    "Other": []
  },
  "Hay and Forage Equipment": {
    "Bale Accumulators / Movers": [],
    "Disc Mowers": [],
    "Mower Conditioners/Windrowers": ["Self-Propelled", "Pull-Type", "Mounted"],
    "Hay Rakes": [],
    "Tedders": [],
    "Rotary Mowers": [],
    "Round Balers": [],
    "Square Balers": ["Large", "Small"],
    "Tub Grinders/Bale Processors": [],
    "Other": []
  },
  "Chemical Applicators": {
    "Sprayers": ["3 pt/Mounted"]
  },
  "Manure Handling": {},
  "Manure Spreaders": {
    "Dry": []
  },
  "Grain Handling / Storage Equipment": {
    "Grain Augers": []
  },
  "Ag Trailers": {
    "Other": []
  },
  "Outdoor Power": {
    "Lawn Mowers": ["Riding"],
    "Snow Blowers": []
  },
  "Other Equipment": {
    "Blades/Box Scrapers": []
  },
  "Turf Equipment": {
    "Mowers": ["Fairway"]
  },
  "Trucks": {
    "Pickup Trucks": ["1/2 Ton"],
    "Service Trucks / Utility Trucks / Mechanic Trucks": [],
    "Truck Bodies Only": ["Other"]
  },
  "Semi-Trailers": {
    "Log Trailers": []
  },
  "Trailers": {
    "Car Hauler Trailers": ["Enclosed", "Open"],
    "Cargo / Enclosed Trailers": [],
    "Dump Trailers": [],
    "Flatbed / Tag Trailers": [],
    "Livestock Trailers": [],
    "Tilt Trailers": [],
    "Landscaping Trailers": [],
    "Utility Trailers": ["ATV", "Snowmobile"],
    "Other Trailers": []
  }
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
