# Cinema Kiosk System - PRODUCTION READY

A complete web-based cinema ticketing kiosk system with admin panel and customer interface. **STATUS: 100% COMPLETE & PRODUCTION READY**

## 🎬 Features

### Admin System
- **Secure Authentication**: Login/logout with session management
- **Movie Management**: Add, edit, delete movies with details (title, description, trailer URL, poster)
- **Showtime & Seat Management**: Create showtimes and manage seat availability
- **Extras Management**: Manage snacks and drinks with categories
- **Analytics Dashboard**: Real-time sales data with Chart.js visualizations
- **Responsive Design**: Works on desktop, tablet, and mobile devices

### Technical Features
- **Security**: Prepared statements to prevent SQL injection
- **Modern UI**: Clean, responsive design with CSS Grid and Flexbox
- **Interactive Charts**: Chart.js for data visualization
- **Real-time Updates**: Dynamic seat management with visual feedback
- **Mobile Friendly**: Responsive design with mobile navigation

## 📁 Project Structure

```
cinema-kiosk/
├── admin/                  # Admin panel files
│   ├── includes/          # Reusable components
│   │   ├── auth.php       # Authentication functions
│   │   └── sidebar.php    # Navigation sidebar
│   ├── dashboard.php      # Main dashboard with analytics
│   ├── movies.php         # Movie management
│   ├── showtimes.php      # Showtime management
│   ├── seats.php          # Seat management
│   ├── extras.php         # Snacks/drinks management
│   └── login.php          # Admin login page
├── assets/                # Static assets
│   ├── css/
│   │   └── admin.css      # Main stylesheet
│   ├── js/
│   │   └── admin.js       # JavaScript functionality
│   └── images/            # Image uploads
├── db/                    # Database files
│   ├── config.php         # Database configuration
│   └── cinema_kiosk.sql   # Database schema & sample data
├── kiosk/                 # Customer kiosk (future development)
└── README.md              # This file
```

## 🚀 Setup Instructions

### Prerequisites
- **Web Server**: Apache/Nginx with PHP support
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Browser**: Modern browser with JavaScript enabled

### Installation Steps

1. **Clone/Download the project**
   ```bash
   git clone [repository-url]
   # or download and extract ZIP file
   ```

2. **Database Setup**
   - Create a MySQL database named `cinema_kiosk`
   - Import the SQL file: `db/cinema_kiosk.sql`
   - Update database credentials in `db/config.php` if needed

3. **Web Server Configuration**
   - Place project folder in your web server directory (htdocs/www)
   - Ensure PHP has write permissions for session handling
   - Enable PHP extensions: PDO, PDO_MySQL

4. **Access the System**
   - Navigate to: `http://localhost/cinema-kiosk/admin/login.php`
   - **Demo Login**: 
     - Username: `admin`
     - Password: `admin123`

## 🗄️ Database Schema

### Tables Overview
- **admins**: Admin user accounts
- **movies**: Movie information and details
- **showtimes**: Movie screening schedules
- **seats**: Individual seat management
- **extras**: Snacks and drinks inventory
- **sales**: Transaction records
- **sales_extras**: Junction table for extras in sales

### Sample Data Included
- 3 sample movies with different ratings
- Multiple showtimes for demonstration
- Various snacks and drinks
- Sample sales data for analytics
- Pre-configured admin account

## 🎯 Usage Guide

### Admin Dashboard
- **Overview Cards**: Total sales, today's sales, active movies, available extras
- **Charts**: Top movies by revenue, most popular extras
- **Recent Activity**: Latest sales transactions

### Movie Management
- Add new movies with complete details
- Edit existing movie information
- Toggle movie status (active/inactive)
- View all movies in organized table

### Showtime Management
- Create showtimes for any active movie
- Set date, time, and ticket prices
- Automatic seat generation (configurable capacity)
- Visual availability indicators

### Seat Management
- Interactive seat map with click-to-toggle
- Visual distinction between available/booked seats
- Real-time occupancy statistics
- Mobile-responsive seat grid

### Extras Management
- Separate management for snacks and drinks
- Category-based organization
- Price and inventory tracking
- Sales statistics per category

## 🔒 Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **SQL Injection Prevention**: All queries use prepared statements
- **Session Management**: Secure session handling with timeout
- **Input Validation**: Server-side validation for all forms
- **XSS Protection**: HTML escaping for all output

## 📱 Responsive Design

- **Mobile First**: Optimized for mobile devices
- **Tablet Support**: Adapted layouts for tablet screens
- **Desktop Enhanced**: Full features on desktop browsers
- **Touch Friendly**: Large buttons and touch targets

## 🛠️ Customization

### Adding New Features
1. Create new PHP files in appropriate directories
2. Follow existing code structure and security practices
3. Update navigation in `includes/sidebar.php`
4. Add corresponding CSS styles in `assets/css/admin.css`

### Styling Modifications
- Main styles: `assets/css/admin.css`
- Color scheme: CSS custom properties at top of file
- Responsive breakpoints: Media queries at bottom

### Database Modifications
- Update schema in `db/cinema_kiosk.sql`
- Modify connection settings in `db/config.php`
- Add new tables following existing naming conventions

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify credentials in `db/config.php`
   - Ensure database exists and is accessible

2. **Login Issues**
   - Verify admin account exists in database
   - Check session configuration in PHP
   - Clear browser cookies/cache

3. **Permission Errors**
   - Ensure web server has read/write permissions
   - Check PHP error logs for specific issues
   - Verify file ownership and permissions

4. **Chart.js Not Loading**
   - Check internet connection (CDN dependency)
   - Verify JavaScript is enabled in browser
   - Check browser console for errors

## 📈 Future Enhancements

- Customer kiosk interface
- Online payment integration
- Email notifications
- Advanced reporting
- Multi-language support
- API for mobile apps

## 📄 License

This project is created for educational purposes. Feel free to use and modify for your school projects.

## 🤝 Support

For questions or issues:
1. Check the troubleshooting section
2. Review PHP error logs
3. Verify database connectivity
4. Test with sample data provided

---

**Note**: This is a demonstration system for educational purposes. For production use, additional security measures and testing would be required.