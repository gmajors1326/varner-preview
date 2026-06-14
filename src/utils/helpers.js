// Map API unit → local unit shape used by the editor
export function apiToLocal(u) {
  return {
    id: u.id,
    title: u.title,
    year: u.year,
    make: u.make,
    model: u.model,
    stockNumber: u.stock_number,
    condition: u.condition,
    price: u.price,
    callForPrice: u.call_for_price ?? false,
    vin: u.vin,
    vinImage: u.vin_image ?? '',
    stockStatus: u.stock_status,
    category: u.category,
    subcategory: u.subcategory ?? '',
    sub_subcategory: u.sub_subcategory ?? '',
    color: u.color,
    length: u.length,
    meter: u.meter,
    meterType: u.meter_type,
    intakeDate: u.intake_date,
    description: u.description,
    sellerInfo: u.seller_info,
    featured: u.featured ?? false,
    showOnWebsite: u.show_on_website ?? true,
    facebookSync: u.facebook_sync ?? true,
    hasAttachments: u.has_attachments ?? false,
    attachmentDetails: u.attachment_details ?? '',
    drive: u.drive ?? '',
    images: u.images ?? [],
    image_ids: u.image_ids ?? [],
    attachments: (u.implements ?? []).map(imp => ({
      image: imp.image,
      image_id: imp.image_id,
      title: imp.title,
      price: imp.price,
      description: imp.description,
    })),
  };
}

export function getDaysInStock(item) {
  const dateStr = item.intakeDate || item.createdAt;
  if (!dateStr) return '-';
  try {
    const datePart = dateStr.split(' ')[0];
    const parts = datePart.split('-');
    if (parts.length < 3) return '-';
    
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    
    const itemDate = new Date(year, month, day);
    const today = new Date();
    itemDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    
    const diffTime = today.getTime() - itemDate.getTime();
    const diffDays = Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
    return `${diffDays} Day${diffDays !== 1 ? 's' : ''}`;
  } catch (e) {
    return '-';
  }
}

// Map inventory list item shape for the table
export function apiToListItem(u) {
  return {
    id: String(u.id),
    wpId: u.id,
    stock: u.stock_number,
    year: u.year,
    make: u.make,
    model: u.model,
    category: u.category,
    subcategory: u.subcategory ?? '',
    sub_subcategory: u.sub_subcategory ?? '',
    condition: u.condition,
    price: u.price,
    callForPrice: u.call_for_price ?? false,
    status: u.stock_status,
    vin: u.vin,
    vinImage: u.vin_image ?? '',
    image: u.images?.[0] ?? '',
    images: u.images ?? [],
    showOnWebsite: u.show_on_website ?? true,
    facebookSync: u.facebook_sync ?? true,
    featured: u.featured ?? false,
    attachments: (u.implements ?? []).map(imp => ({
      image: imp.image,
      image_id: imp.image_id ?? 0,
      title: imp.title,
      price: imp.price,
      description: imp.description,
    })),
    hasAttachments: u.has_attachments ?? false,
    attachmentDetails: u.attachment_details ?? '',
    drive: u.drive ?? '',
    deleted_at: u.deleted_at ?? '',
    intakeDate: u.intake_date,
    createdAt: u.created_at,
  };
}
