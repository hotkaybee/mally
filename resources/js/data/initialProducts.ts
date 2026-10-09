import { Product } from '../types/store';

export const INITIAL_PRODUCTS: Product[] = [
  {
    id: 1,
    sku: 'ML-HG-01',
    name: 'Hourglass Vanish Airbrush Concealer',
    brand: 'Hourglass',
    category: 'Макияж',
    selling_price: 38.00,
    old_price: 44.00,
    quantity: 14,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=700&q=80',
    images: [
      'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=700&q=80',
      'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=700&q=80'
    ],
    description: 'Легендарный невесомый консилер с полным перекрытием и эффектом фотошопа. Скрывает темные круги и несовершенства, не забиваясь в мелкие морщинки. Водостойкая формула держится до 16 часов.',
    howToUse: 'Нанесите 1-2 микрокапли под глаза или на проблемные участки. Растушуйте кистью или влажным спонжем легкими вбивающими движениями.',
    features: ['Водостойкая формула 16 часов', 'Ультра-легкая текстура', 'Веганский состав без парабенов']
  },
  {
    id: 2,
    sku: 'ML-RHD-01',
    name: 'Rhode Glazing Milk Ceramide Essence',
    brand: 'Rhode',
    category: 'Уход',
    selling_price: 40.00,
    old_price: 48.00,
    quantity: 18,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1608248597359-59754f285186?auto=format&fit=crop&w=700&q=80',
    images: [
      'https://images.unsplash.com/photo-1608248597359-59754f285186?auto=format&fit=crop&w=700&q=80'
    ],
    description: 'Питательная молочная эссенция от Хейли Бибер с комплексом церамидов. Моментально восстанавливает кожный барьер, снимает покраснения и дарит фирменное фирменное сияние глазированной кожи («glazed donut»).',
    howToUse: 'Встряхните флакон, нанесите небольшое количество на ладони и аккуратно прижмите к очищенной коже лица и шеи перед сывороткой.',
    features: ['Укрепляет гидролипидный барьер', 'Снижает чувствительность', 'Идеальная база под макияж']
  },
  {
    id: 3,
    sku: 'ML-RHD-02',
    name: 'Rhode Pocket Blush / Toasted Teddy',
    brand: 'Rhode',
    category: 'Макияж',
    selling_price: 32.00,
    old_price: null,
    quantity: 12,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=700&q=80',
    description: 'Кремовые румяна в ультракомпактном формате для естественного сияющего румянца. Тающая текстура легко растушевывается подушечками пальцев прямо на ходу.',
    howToUse: 'Нанесите стиком прямо на скулы и губы, мягко растушуйте пальцами до желаемой интенсивности цвета.',
    features: ['Пептидный комплекс', 'Сатиновый финиш', 'Подходит для губ и щек']
  },
  {
    id: 4,
    sku: 'ML-SDJ-01',
    name: 'Sol de Janeiro Cheirosa 68 Beija Flor Perfume Mist (240ml)',
    brand: 'Sol de Janeiro',
    category: 'Парфюмерия',
    selling_price: 39.00,
    old_price: 45.00,
    quantity: 10,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=700&q=80',
    description: 'Вирусный парфюмированный мист для тела и волос с нотами бразильского жасмина, розового драконьего фрукта и ванили. Аромат цветущего сада в Рио.',
    howToUse: 'Распыляйте по всему телу, волосам и одежде в течение дня для освежающего цветочного шлейфа.',
    features: ['Ноты жасмина и питайи', 'Стойкий деликатный шлейф', 'Бестселлер TikTok']
  },
  {
    id: 5,
    sku: 'ML-RIT-01',
    name: 'Rituals The Ritual of Ayurveda Gift Set Medium',
    brand: 'Rituals',
    category: 'Наборы',
    selling_price: 55.00,
    old_price: 65.00,
    quantity: 8,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1512290900672-1f5be57d19e9?auto=format&fit=crop&w=700&q=80',
    description: 'Роскошный подарочный набор на основе индийской розы и масла сладкого миндаля. Включает пенящийся гель для душа, крем для тела, ароматическую свечу и скраб.',
    howToUse: 'Используйте гель и скраб во время душа, затем нанесите крем для бархатистой гладкости кожи.',
    features: ['Подарочная крафтовая коробка', 'Аромат дамасской розы', 'Идеально на подарок']
  },
  {
    id: 6,
    sku: 'ML-DIOR-01',
    name: 'Dior Forever Skin Glow Foundation',
    brand: 'Dior',
    category: 'Макияж',
    selling_price: 67.00,
    old_price: null,
    quantity: 6,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1631729371254-42c2892f0e6e?auto=format&fit=crop&w=700&q=80',
    description: 'Культовый сияющий тональный флюид от Dior с 86% уходовой базы. Выравнивает тон, обеспечивает 24 часа стойкости и непревзойденное естественное сияние.',
    howToUse: 'Наносите от центра лица к периферии кистью или бьюти-блендером для гладкого ровного покрытия.',
    features: ['SPF 20 защита', '24 часа стойкости', '86% цветочный уход']
  },
  {
    id: 7,
    sku: 'ML-GIS-01',
    name: 'Gisou Honey Infused Lip Oil / Watermelon Sugar',
    brand: 'Gisou',
    category: 'Макияж',
    selling_price: 28.00,
    old_price: 34.00,
    quantity: 15,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=700&q=80',
    description: 'Масло для губ с натуральным пчелиным медом Мирсалехи и гиалуроновой кислотой. Глубоко питает, смягчает губы и придает сочный зеркальный блеск без липкости.',
    howToUse: 'Наносите на чистые губы в течение дня или поверх любимой помады для создания зеркального объема.',
    features: ['Натуральный мед Mirsalehi', 'Без ощущения липкости', 'Аромат спелого арбуза']
  },
  {
    id: 8,
    sku: 'ML-MARIO-01',
    name: 'Makeup by Mario Master Mattes Eyeshadow Palette',
    brand: 'Makeup by Mario',
    category: 'Макияж',
    selling_price: 77.00,
    old_price: 85.00,
    quantity: 5,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=700&q=80',
    description: 'Базовая профессиональная палитра из 12 универсальных матовых оттенков от звездного визажиста Марио Дедивановича. Бархатистая текстура с легкой растушевкой.',
    howToUse: 'Используйте светлые оттенки как базу, средние тона в складку века, а темные для глубины и стрелок.',
    features: ['12 культовых оттенков', 'Шелковистый помол', 'Создано звездным визажистом']
  },
  {
    id: 9,
    sku: 'ML-RARE-01',
    name: 'Rare Beauty Soft Pinch Liquid Blush / Happy',
    brand: 'Rare Beauty',
    category: 'Макияж',
    selling_price: 32.90,
    old_price: null,
    quantity: 9,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=700&q=80',
    description: 'Невесомые стойкие жидкие румяна от Селены Гомес. Всего одной капли достаточно для создания свежего здорового румянца на весь день.',
    howToUse: 'Поставьте 1 микроточку аппликатором на щеку и быстро растушуйте пальцами или плотной кистью.',
    features: ['Стойкость до 12 часов', 'Легкая тушевка', 'Формула от Селены Гомес']
  },
  {
    id: 10,
    sku: 'ML-SDJ-02',
    name: 'Sol de Janeiro Brazilian Bum Bum Cream (240ml)',
    brand: 'Sol de Janeiro',
    category: 'Уход',
    selling_price: 49.00,
    old_price: 55.00,
    quantity: 7,
    status: 'in_stock',
    image: 'https://images.unsplash.com/photo-1608248597359-59754f285186?auto=format&fit=crop&w=700&q=80',
    description: 'Знаменитый подтягивающий крем для тела с экстрактом гуараны, маслом купуасу и ароматом фисташек и соленой карамели. Быстро впитывается, оставляя кожу гладкой.',
    howToUse: 'Массажными круговыми движениями наносите на тело для согревания и лучшего проникновения активных компонентов.',
    features: ['Экстракт гуараны', 'Быстрое впитывание', 'Аромат фисташки и карамели']
  }
];

export const STORE_CATEGORIES = [
  'Все',
  'Макияж',
  'Уход',
  'Парфюмерия',
  'Наборы',
  'Избранное'
];

export const STORE_BRANDS = [
  'Все бренды',
  'Rhode',
  'Hourglass',
  'Rituals',
  'Rare Beauty',
  'Sol de Janeiro',
  'Dior',
  'Gisou',
  'Makeup by Mario'
];
