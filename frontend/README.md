# Apple E-commerce Frontend

A Vue 3 + Vite frontend application for the Apple e-commerce platform, separated from the Laravel backend.

## Features

- Vue 3 with Composition API
- Vite for fast development and building
- Vue Router for navigation
- Pinia for state management
- Axios for API communication
- Bootstrap 5 for styling
- FontAwesome icons
- Toast notifications

## Project Structure

```
frontend/
├── src/
│   ├── components/          # Vue components
│   │   ├── Cart/           # Cart-related components
│   │   ├── Product/        # Product-related components
│   │   ├── header/         # Header components
│   │   ├── navbar/         # Navigation components
│   │   ├── HomePage/       # Home page components
│   │   ├── Profile/        # Profile components
│   │   ├── loading/        # Loading components
│   │   └── status/         # Status components
│   ├── pages/              # Page components
│   ├── router/             # Vue Router configuration
│   ├── services/           # API service layer
│   ├── stores/             # Pinia stores
│   ├── assets/             # Static assets
│   ├── App.vue             # Root component
│   └── main.js             # Application entry point
├── public/                 # Public assets
├── index.html              # HTML template
├── package.json            # Dependencies
├── vite.config.js          # Vite configuration
└── README.md               # This file
```

## Installation

1. Install dependencies:
```bash
npm install
```

2. Start development server:
```bash
npm run dev
```

The application will be available at `http://localhost:3000`

## API Integration

The frontend is configured to communicate with the Laravel backend API. The API base URL is set to `/api` and is proxied to `http://127.0.0.1:8000/api` during development.

### Services

- `authService.js` - Authentication operations
- `productService.js` - Product operations
- `cartService.js` - Shopping cart operations
- `orderService.js` - Order management

### Stores

- `auth.js` - Authentication state
- `cart.js` - Shopping cart state
- `productStore.js` - Product state
- `loading.js` - Loading state
- `wishlist.js` - Wishlist state
- `buynow.js` - Buy now functionality

## Build for Production

```bash
npm run build
```

The build output will be in the `dist/` directory.

## Environment Configuration

The development server is configured with a proxy to forward API requests to the Laravel backend. Update the `vite.config.js` file if your backend runs on a different address.

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build

## Dependencies

### Main Dependencies
- Vue 3
- Vue Router 4
- Pinia
- Axios
- Bootstrap 5
- FontAwesome
- Vue Toastification

### Dev Dependencies
- Vite
- @vitejs/plugin-vue

## Notes

- The frontend uses `@` alias for `src/` directory imports
- All API calls go through the service layer for better organization
- State management is handled by Pinia stores with persistence
- The application is fully responsive and uses Bootstrap for styling
