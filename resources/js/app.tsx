import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import StoreApp from './storefront/StoreApp';

// Mount Storefront if container exists
const storeContainer = document.getElementById('store-app');
if (storeContainer) {
  const root = createRoot(storeContainer);
  root.render(
    <React.StrictMode>
      <StoreApp />
    </React.StrictMode>
  );
}
