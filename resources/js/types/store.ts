export interface Product {
  id: string | number;
  sku: string;
  name: string;
  slug?: string;
  description?: string;
  comment?: string;
  selling_price: number;
  old_price?: number | null;
  quantity: number;
  status: string; // 'in_stock' | 'out_of_stock' | 'ACTIVE' | etc.
  image?: string | null;
  images?: string[];
  link?: string | null;
  brand_id?: number | null;
  category_id?: number | null;
  brand?: {
    id: number;
    name: string;
    website?: string;
    logo?: string | null;
  } | string;
  category?: {
    id: number;
    name: string;
    name_ru?: string;
    name_uz?: string;
    name_en?: string;
    icon?: string;
  } | string;
  features?: string[];
  howToUse?: string;
}

export interface Category {
  id: number;
  name: string;
  name_ru?: string;
  name_uz?: string;
  name_en?: string;
  icon?: string;
  count?: number;
}

export interface Brand {
  id: number;
  name: string;
  website?: string;
  logo?: string | null;
  count?: number;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface OrderItemPayload {
  product_id: string | number;
  quantity: number;
  price: number;
}

export interface CheckoutForm {
  first_name: string;
  last_name?: string;
  phone_number: string;
  telegram?: string;
  instagram?: string;
  city: string;
  address: string;
  comment?: string;
}

export interface CreatedOrder {
  id: string | number;
  order_number: string | number;
  total_amount: number;
  status: string;
  customer_name: string;
  phone_number: string;
  created_at: string;
}
