# Network Complaint Tracking System
## Enrollment: 230210107029

### Setup Instructions (XAMPP)
1. Start Apache and MySQL in XAMPP Control Panel
2. Copy this entire folder to `C:/xampp/htdocs/complaint_system/`
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Import `database.sql` to create the database and tables
5. Visit `http://localhost/complaint_system/login.php`

### Demo Credentials
| Role | Username | Password |
|------|----------|----------|
| Supervisor/Admin | admin | admin123 |
| Staff | staff1 | staff123 |
| Staff | staff2 | staff123 |
| Complainant | user1 | user123 |
| Complainant | user2 | user123 |

### Features
- Role-based login (Complainant, Staff, Supervisor)
- PHP Sessions + Cookie-based features
- Complaint registration with file upload
- Dependent dropdowns (Zone → Sector → Spot) via AJAX
- Complaint workflow & status tracking with timeline
- SLA monitoring (5h initial, 48h resolution)
- Repeated complaint flagging (Special Rule: U is odd)
- Staff assignment by Supervisor
- Action proof upload by Staff
- AJAX live complaint tracking
- Reports: Reopened complaints (R=5), Category-wise, Zone-wise, SLA breaches
- 4 JSON API endpoints
- Client-side + Server-side validation
- Duplicate complaint detection (AJAX)

### API Endpoints
- `api/track_complaint.php?code=CMP-2025-001` - Track complaint
- `api/area_pending.php` - Area-wise pending complaints
- `api/category_summary.php` - Category summary
- `api/get_sectors.php?zone_id=1` - Get sectors by zone
- `api/get_spots.php?sector_id=1` - Get spots by sector
- `api/check_duplicate.php?title=...&spot_id=...` - Duplicate check