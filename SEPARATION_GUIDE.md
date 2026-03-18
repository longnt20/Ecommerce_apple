# Frontend Separation Guide

This document explains how to run the Apple E-commerce project after separating the frontend from Laravel.

## Project Structure

```
Apple-Project/
├── backend/                 # Laravel API only
│   ├── app/
│   ├── routes/
│   ├── database/
│   └── ...
├── frontend/               # Vue 3 + Vite application
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── vite.config.js
└── SEPARATION_GUIDE.md     # This file
```

## Setup Instructions

### 1. Backend Setup (Laravel API)

1. Navigate to the project root:
```bash
cd f:\laragon\www\Apple-Project
```

2. Install Laravel dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your `.env` file for database and other settings

6. Run migrations:
```bash
php artisan migrate
```

7. Start Laravel development server:
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`

### 2. Frontend Setup (Vue 3 + Vite)

1. Navigate to the frontend directory:
```bash
cd frontend
```

2. Install Node.js dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run dev
```

The frontend will be available at `http://localhost:3000`

## API Endpoints

The Laravel backend now serves as an API-only application. Main endpoints:

### Authentication
- `POST /api/login` - User login
- `POST /api/register` - User registration
- `GET /api/user` - Get current user
- `POST /api/logout` - User logout

### Products
- `GET /api/products` - List products
- `GET /api/products/{slug}` - Get product by slug
- `GET /api/products/featured` - Featured products
- `GET /api/products/hot-sale` - Hot sale products

### Cart
- `GET /api/cart` - Get user cart
- `POST /api/cart/add` - Add item to cart
- `PUT /api/cart/{id}` - Update cart item
- `DELETE /api/cart/{id}` - Remove cart item

### Orders
- `POST /api/orders` - Create order
- `GET /api/orders` - Get user orders
- `GET /api/orders/{id}` - Get order details

## Development Workflow

### Running Both Applications

1. Terminal 1 - Start Laravel API:
```bash
cd f:\laragon\www\Apple-Project
php artisan serve
```

2. Terminal 2 - Start Vue frontend:
```bash
cd f:\laragon\www\Apple-Project\frontend
npm run dev
```

### API Proxy Configuration

The Vite development server is configured to proxy API requests to the Laravel backend:

```javascript
// vite.config.js
server: {
  proxy: {
    '/api': {
      target: 'http://127.0.0.1:8000',
      changeOrigin: true,
      secure: false,
    },
  },
}
```

This means you can make API calls from the frontend using `/api/*` URLs, and they will be automatically forwarded to the Laravel backend.

## Production Deployment

### Backend (Laravel)

1. Configure your production environment variables
2. Run `php artisan config:cache`
3. Run `php artisan route:cache`
4. Set up web server (Apache/Nginx) to point to the `public` directory
5. Configure SSL certificates

### Frontend (Vue)

1. Build the application:
```bash
npm run build
```

2. Deploy the `dist/` folder to your web server or CDN
3. Configure the web server to serve the SPA properly

## Benefits of Separation

1. **Independent Development**: Frontend and backend can be developed separately
2. **Scalability**: Each application can be scaled independently
3. **Technology Flexibility**: Frontend can use modern Vue.js features without Laravel constraints
4. **Better Performance**: Optimized build process and asset management
5. **Team Separation**: Different teams can work on frontend and backend simultaneously

## Troubleshooting

### CORS Issues
If you encounter CORS errors, ensure your Laravel backend has proper CORS configuration. You may need to add CORS middleware or configure it in your `bootstrap/app.php`.

### API Connection Issues
- Verify Laravel server is running on port 8000
- Check the proxy configuration in `vite.config.js`
- Ensure API endpoints are correctly defined in Laravel routes

### Build Issues
- Clear Node.js modules and reinstall: `rm -rf node_modules && npm install`
- Check for any missing dependencies in `package.json`

## Next Steps

1. Test all API endpoints to ensure they work correctly
2. Verify frontend functionality with the separated backend
3. Set up proper error handling and loading states
4. Implement authentication guards in the frontend
5. Add comprehensive testing for both applications
