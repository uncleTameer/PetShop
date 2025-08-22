# PetShop Enhanced E-commerce Platform

A comprehensive e-commerce platform for pet supplies with advanced features including user management, loyalty points, reviews, Q&A, alerts, and admin analytics.

## 🚀 Features Implemented

### 🔐 Authentication & Security

- **Email/Password Login** with proper validation and flash messaging
- **Google OAuth 2.0** integration for seamless login and registration
- **Brute-force Protection**: Account lockout after 3 failed attempts (15-minute duration)
- **IP-based Throttling** for login attempts
- **Input Validation & Sanitization** across all forms
- **CSRF Protection** on all forms
- **Session Security** with secure cookies and timeout management
- **Password Strength Requirements** (minimum 6 characters)

### 👤 User Management

- **Enhanced User Profiles** with avatar uploads
- **Multiple Address Management** (label, name, phone, address lines, city, zip, country)
- **User Preferences** including default category and low stock threshold
- **Device Session Management** (IP, User Agent, last seen)
- **Force Logout from Other Devices** for security
- **Account Audit Trail** (creation, login history, IP tracking)

### 🛒 Shopping Features

- **Enhanced Cart System** with guest and user cart support
- **Guest Cart Expiration** (30-minute timer for abandoned carts)
- **Wishlist Management** with price tracking
- **Product Filters** (price range, in-stock, rating, category, search)
- **Stock Management** with low stock alerts and customizable thresholds
- **Related Products** suggestions

### ⭐ Reviews & Q&A System

- **Product Reviews** with 1-5 star ratings
- **Verified Purchase Badges** for authentic reviews
- **Photo Uploads** for reviews (JPG, PNG, WEBP, 2MB max)
- **Questions & Answers** system for products
- **Admin Moderation** for Q&A responses
- **Review Pagination** and helpful voting system

### 🔔 Smart Alerts & Notifications

- **Back-in-Stock Alerts** for out-of-stock products
- **Price-Drop Alerts** with customizable target prices
- **Low Stock Notifications** for admins
- **Email Notifications** for important events
- **Real-time Alert Processing** and triggering

### 💎 Loyalty Points System

- **Earn Points**: 10% of order value in loyalty points
- **Redeem Points**: Use points as currency (1 point = $0.01)
- **Minimum Redemption**: 100 points required
- **Automatic Awarding** upon order completion
- **Point Balance Tracking** in user profiles

### 📊 Admin Dashboard & Analytics

- **Comprehensive Statistics**: Sales, AOV, conversion rates
- **Top Products Analysis** by quantity and revenue
- **Customer Churn Analysis** with period comparisons
- **Low Stock Monitoring** with customizable thresholds
- **Real-time Notifications** for system events
- **Period-based Reporting** (week, month, quarter, year)
- **Interactive Charts** using Chart.js

### 🛡️ File Upload Security

- **MIME Type Validation** using finfo
- **File Size Limits** (2MB maximum)
- **Allowed Formats**: JPG, PNG, WEBP only
- **Safe Path Handling** with realpath validation
- **Automatic Cleanup** of old files
- **Avatar Upload Management** for user profiles

### 🔧 Technical Features

- **MongoDB Integration** with proper indexing
- **Database Schema Documentation** in SQL format
- **Modular Architecture** with separate manager classes
- **Error Handling** with debug mode support
- **Configuration Management** with environment variables
- **Performance Optimization** with database indexes
- **Responsive Design** using Bootstrap 5

## 🗄️ Database Collections

### Users Collection

```javascript
{
  _id: ObjectId,
  fullName: String,
  email: String (unique),
  password: String (hashed),
  role: String (user/admin),
  profilePicture: String,
  addresses: [{
    label: String,
    name: String,
    phone: String,
    line1: String,
    line2: String,
    city: String,
    zip: String,
    country: String,
    isDefault: Boolean
  }],
  preferences: {
    defaultCategoryId: ObjectId,
    emailNotifications: Boolean,
    lowStockThreshold: Number
  },
  loyaltyPoints: Number,
  security: {
    twoFactorEnabled: Boolean,
    twoFactorSecret: String,
    lastPasswordChange: Date
  },
  lockout: {
    loginAttempts: Number,
    locked: Boolean,
    lockoutTime: Date
  },
  audit: {
    createdAt: Date,
    createdIp: String,
    createdUA: String,
    lastLoginAt: Date,
    lastLoginIp: String,
    lastLoginUA: String
  }
}
```

### Products Collection

```javascript
{
  _id: ObjectId,
  name: String,
  description: String,
  price: Number,
  originalPrice: Number,
  image: String,
  category: String,
  stock: Number,
  lowStockThreshold: Number,
  status: String,
  averageRating: Number,
  createdAt: Date,
  updatedAt: Date
}
```

### Reviews Collection

```javascript
{
  _id: ObjectId,
  userId: ObjectId,
  productId: ObjectId,
  rating: Number (1-5),
  text: String,
  photos: [String],
  verifiedPurchase: Boolean,
  helpful: Number,
  createdAt: Date,
  updatedAt: Date
}
```

### Orders Collection

```javascript
{
  _id: ObjectId,
  userId: ObjectId,
  items: [{
    productId: ObjectId,
    name: String,
    price: Number,
    quantity: Number
  }],
  subtotal: Number,
  loyaltyPointsEarned: Number,
  loyaltyPointsUsed: Number,
  loyaltyDiscount: Number,
  finalTotal: Number,
  shippingAddress: Object,
  status: String,
  createdAt: Date,
  updatedAt: Date
}
```

## 🚀 Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MongoDB server
- Composer
- Web server (Apache/Nginx)

### 1. Clone the Repository

```bash
git clone <repository-url>
cd PetShop
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the configuration file and update with your settings:

```bash
cp php/config.php.example php/config.php
```

Update the following in `php/config.php`:

- Database connection details
- Google OAuth credentials
- SMTP settings for email
- File upload paths

### 4. Database Setup

The system will automatically create necessary collections and indexes on first run.

### 5. File Permissions

Ensure the uploads directory is writable:

```bash
chmod 755 uploads/
```

### 6. Web Server Configuration

Point your web server to the project directory and ensure PHP has MongoDB extension enabled.

## 🔧 Configuration

### Environment Variables

Key configuration options in `php/config.php`:

```php
// Security
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_DURATION', 900); // 15 minutes
define('GUEST_CART_EXPIRY', 1800); // 30 minutes

// File Uploads
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Loyalty Points
define('LOYALTY_POINTS_PERCENTAGE', 10); // 10% of order value
define('MIN_LOYALTY_POINTS_REDEMPTION', 100);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('REVIEWS_PER_PAGE', 5);
define('QA_PER_PAGE', 10);
```

### Google OAuth Setup

1. Create a Google Cloud Project
2. Enable Google+ API
3. Create OAuth 2.0 credentials
4. Update `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in config

### SMTP Configuration

Update email settings for notifications:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

## 📱 Usage

### User Features

#### Shopping

- Browse products with advanced filters
- Add items to cart (guest or logged-in)
- Manage wishlist
- Set price and stock alerts
- Complete checkout with loyalty points

#### Reviews & Q&A

- Write product reviews with photos
- Ask questions about products
- View verified purchase badges
- Rate helpfulness of content

#### Account Management

- Manage multiple shipping addresses
- Track loyalty points balance
- View order history
- Manage device sessions
- Update profile and preferences

### Admin Features

#### Dashboard

- View comprehensive sales analytics
- Monitor low stock products
- Track user conversion rates
- Analyze customer churn
- Period-based reporting

#### Product Management

- Add/edit products with images
- Set stock thresholds
- Manage categories
- Monitor inventory levels

#### Order Management

- Process and update order status
- View order details
- Manage shipping information
- Track loyalty points usage

#### User Management

- View user accounts
- Monitor login attempts
- Manage user roles
- View user activity

## 🔒 Security Features

### Authentication Security

- Password hashing with bcrypt
- Account lockout protection
- IP-based throttling
- Session timeout management
- CSRF token validation

### File Upload Security

- MIME type validation
- File size restrictions
- Safe path handling
- Allowed format restrictions
- Automatic cleanup

### Data Protection

- Input sanitization
- SQL injection prevention
- XSS protection
- Secure session handling

## 📊 Performance Optimization

### Database Indexes

- User email uniqueness
- Product category and stock
- Order status and dates
- Review and Q&A relationships
- Cart expiration tracking

### Caching Strategy

- Session-based caching
- Database query optimization
- Image optimization
- CDN-ready file structure

## 🧪 Testing

### Manual Testing Checklist

- [ ] User registration and login
- [ ] Google OAuth integration
- [ ] Product browsing and filtering
- [ ] Cart management (guest and user)
- [ ] Wishlist functionality
- [ ] Review and Q&A systems
- [ ] Order placement and management
- [ ] Loyalty points earning and redemption
- [ ] Alert system functionality
- [ ] Admin dashboard features
- [ ] File upload security
- [ ] Security features (lockout, throttling)

### Automated Testing

The system is designed to be testable with proper error handling and logging.

## 🚨 Troubleshooting

### Common Issues

#### Database Connection

- Verify MongoDB server is running
- Check connection string in config
- Ensure PHP MongoDB extension is installed

#### File Uploads

- Check upload directory permissions
- Verify file size limits in PHP config
- Ensure allowed MIME types are correct

#### Google OAuth

- Verify OAuth credentials are correct
- Check redirect URI matches exactly
- Ensure Google+ API is enabled

#### Email Notifications

- Verify SMTP credentials
- Check firewall settings
- Test with simple email first

### Debug Mode

Enable debug mode in config for detailed error messages:

```php
define('DEBUG_MODE', true);
```

## 🔄 Maintenance

### Regular Tasks

- Monitor low stock alerts
- Review user feedback and Q&A
- Analyze sales and conversion data
- Clean up expired guest carts
- Backup database regularly

### Performance Monitoring

- Monitor database query performance
- Check file upload usage
- Track user session data
- Monitor alert system performance

## 📈 Future Enhancements

### Planned Features

- Mobile app integration
- Advanced analytics dashboard
- Multi-language support
- Advanced inventory management
- Integration with shipping providers
- Advanced loyalty program features

### Scalability Considerations

- Database sharding for large datasets
- CDN integration for media files
- Load balancing for high traffic
- Microservices architecture
- API rate limiting

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:

- Create an issue in the repository
- Check the troubleshooting section
- Review the configuration documentation

## 🙏 Acknowledgments

- Bootstrap for responsive design
- Chart.js for data visualization
- Font Awesome for icons
- MongoDB for database
- Google OAuth for authentication

---

**Note**: This is a comprehensive e-commerce platform. Ensure you have proper security measures in place before deploying to production, including HTTPS, proper server configuration, and regular security updates.
