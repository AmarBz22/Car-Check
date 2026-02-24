# VinCheck Frontend Integration Guide

## Table of Contents
1. [Project Overview](#project-overview)
2. [Authentication System](#authentication-system)
3. [User Roles & Status](#user-roles--status)
4. [API Endpoints](#api-endpoints)
5. [Report Management](#report-management-endpoints)
6. [Partner Registration Flow](#partner-registration-flow)
7. [Implementation Checklist](#implementation-checklist)
8. [Error Handling](#error-handling)
9. [Email Templates](#email-templates)

---

## Project Overview

**VinCheck** is a vehicle verification platform with three user roles:
- **Admin**: Approves partner requests, manages the platform
- **Partner**: Professional vehicle inspectors
- **Client**: Vehicle owners requesting inspections

**Tech Stack**:
- Backend: Laravel 12 API (Sanctum authentication)
- Frontend: React/Vue.js (to be developed)
- Database: MySQL
- Email: Gmail SMTP

**API Base URL**: `http://localhost:8000/api` (Development)

---

## Authentication System

### How It Works

1. **Sanctum Tokens**: All API requests require a bearer token (except public endpoints)
2. **Token Format**: `Authorization: Bearer YOUR_TOKEN_HERE`
3. **Password Reset**: Email-based with 60-minute expiry tokens
4. **Role-Based Access**: Middleware checks user role for protected routes

### Token Header Example
```http
GET /api/admin/partner-requests HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|7Qk8NmPqRsT9VwXyZ...
Content-Type: application/json
```

---

## User Roles & Status

### User Roles
| Role | Description | Permissions |
|------|-------------|-------------|
| `admin` | Platform administrator | Approve/reject partners, manage system |
| `partner` | Professional inspector | Create reports, view vehicles |
| `client` | Vehicle owner | Request inspections, upload vehicles |

### User Status
| Status | Description | Can Login | Notes |
|--------|-------------|-----------|-------|
| `active` | Fully activated | ✅ Yes | Ready to use platform |
| `approved` | Approved by admin | ❌ No | Must set password first |
| `suspended` | Blocked account | ❌ No | Admin can suspend partners |

### Status Transitions
```
Client Registration → active (immediately)
Partner Request → pending (awaiting admin)
Admin Approval → approved (email sent with password link)
Password Set → active (ready to login)
```

---

## API Endpoints

### 🔓 Public Endpoints (No Authentication Required)

#### 1. Client Registration
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

**Response (201)**:
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "client",
    "status": "active",
    "created_at": "2026-02-14T10:00:00Z"
  },
  "token": "1|7Qk8NmPqRsT9VwXyZ..."
}
```

**Errors**:
- `422`: Email already exists, password too short, validation failed
- `400`: Missing fields

---

#### 2. Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "Password123"
}
```

**Response (200)**:
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "client",
    "status": "active"
  },
  "token": "1|7Qk8NmPqRsT9VwXyZ..."
}
```

**Errors**:
- `401`: Invalid credentials
- `422`: User not found or account suspended

---

#### 3. Partner Registration Request
```http
POST /api/partner-registration-request
Content-Type: application/json

{
  "name": "Amar Bouzida",
  "email": "amar@example.com",
  "company_name": "VinCheck Partners Ltd",
  "phone": "+213696451116",
  "reason": "Professional vehicle inspection services"
}
```

**Response (201)**:
```json
{
  "message": "Registration request submitted successfully",
  "request": {
    "id": 1,
    "name": "Amar Bouzida",
    "email": "amar@example.com",
    "company_name": "VinCheck Partners Ltd",
    "phone": "+213696451116",
    "reason": "Professional vehicle inspection services",
    "status": "pending",
    "created_at": "2026-02-14T10:00:00Z"
  }
}
```

**Errors**:
- `422`: Email already exists, missing fields, validation failed
- `400`: Invalid data format

---

#### 4. Password Reset (After Admin Approval)
```http
POST /api/reset-password
Content-Type: application/json

{
  "email": "amar@example.com",
  "token": "as8d9f7as8d7as8f7... (from email link)",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}
```

**Response (200)**:
```json
{
  "message": "Password reset successfully",
  "user": {
    "id": 2,
    "name": "Amar Bouzida",
    "email": "amar@example.com",
    "role": "partner",
    "status": "active"
  }
}
```

**Errors**:
- `422`: Invalid token, expired link, password mismatch, validation failed
- `401`: Token not found or revoked
- `400`: Missing fields

---

### 🔐 Protected Endpoints (Require Authentication)

#### 5. Logout
```http
POST /api/logout
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "message": "Logged out successfully"
}
```

---

### 🛡️ Admin Only Endpoints

#### 6. Get Pending Partner Requests
```http
GET /api/admin/partner-requests
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

**Query Parameters**:
- `page`: Pagination (default: 1)
- `per_page`: Items per page (default: 10)

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Amar Bouzida",
      "email": "amar@example.com",
      "company_name": "VinCheck Partners Ltd",
      "phone": "+213696451116",
      "reason": "Professional vehicle inspection services",
      "status": "pending",
      "created_at": "2026-02-14T10:00:00Z"
    }
  ],
  "current_page": 1,
  "total": 5,
  "per_page": 10
}
```

**Errors**:
- `403`: Not authorized (non-admin user)
- `401`: No authentication token

---

#### 7. Get Request Details
```http
GET /api/admin/partner-requests/1
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "id": 1,
  "name": "Amar Bouzida",
  "email": "amar@example.com",
  "company_name": "VinCheck Partners Ltd",
  "phone": "+213696451116",
  "reason": "Professional vehicle inspection services",
  "status": "pending",
  "created_at": "2026-02-14T10:00:00Z",
  "updated_at": "2026-02-14T10:00:00Z"
}
```

**Errors**:
- `404`: Request not found
- `403`: Not authorized
- `401`: No authentication token

---

#### 8. Approve Partner Request
```http
POST /api/admin/partner-requests/1/approve
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

**Response (201)**:
```json
{
  "message": "Partner approved successfully. Email sent with password setup link.",
  "user": {
    "id": 2,
    "name": "Amar Bouzida",
    "email": "amar@example.com",
    "role": "partner",
    "status": "approved",
    "password": null,
    "created_at": "2026-02-14T10:00:00Z"
  },
  "request": {
    "id": 1,
    "status": "approved",
    "updated_at": "2026-02-14T10:00:00Z"
  }
}
```

**What Happens**:
1. New User created with `role=partner`, `status=approved`, `password=null`
2. Password reset token generated (valid for 60 minutes)
3. Email sent to partner with password setup link
4. Registration request marked as `approved`

**Errors**:
- `422`: User with email already exists, validation failed
- `400`: Request already processed (duplicate approval attempt)
- `404`: Request not found
- `403`: Not authorized
- `401`: No authentication token

---

#### 9. Reject Partner Request
```http
POST /api/admin/partner-requests/1/reject
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "message": "Partner request rejected",
  "request": {
    "id": 1,
    "status": "rejected",
    "updated_at": "2026-02-14T10:00:00Z"
  }
}
```

**Errors**:
- `400`: Request already processed
- `404`: Request not found
- `403`: Not authorized
- `401`: No authentication token

---

#### 10. Create Partner Directly (Admin - Option 1: With Password)
```http
POST /api/admin/partners/create
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "name": "Amar Bouzida",
  "email": "amar@example.com",
  "company_name": "VinCheck Partners Ltd",
  "phone": "+213696451116",
  "password": "SecurePassword123"
}
```

**Response (201)**:
```json
{
  "message": "Partner created successfully. Password set - ready to login.",
  "user": {
    "id": 2,
    "name": "Amar Bouzida",
    "email": "amar@example.com",
    "role": "partner",
    "status": "active",
    "created_at": "2026-02-14T10:00:00Z"
  },
  "company_name": "VinCheck Partners Ltd",
  "phone": "+213696451116",
  "login_ready": true
}
```

**What Happens**:
1. New partner user created with `role=partner`, `status=active`
2. Password is set immediately (no email needed)
3. Partner can login immediately with provided credentials
4. No password reset token needed

**Use Cases**:
- Quick onboarding with instant access
- Admin-controlled password setup
- Bulk partner creation with known credentials
- Direct recruitment with immediate activation

---

#### 10b. Create Partner Directly (Admin - Option 2: Without Password, Send Email)
```http
POST /api/admin/partners/create
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "name": "Sara Mechanic",
  "email": "sara@example.com",
  "company_name": "Sara's Garage",
  "phone": "+213555123456"
}
```

**Response (201)**:
```json
{
  "message": "Partner created successfully. Password setup email sent.",
  "user": {
    "id": 3,
    "name": "Sara Mechanic",
    "email": "sara@example.com",
    "role": "partner",
    "status": "approved",
    "created_at": "2026-02-14T10:00:00Z"
  },
  "company_name": "Sara's Garage",
  "phone": "+213555123456",
  "login_ready": false,
  "email_sent": true
}
```

**What Happens**:
1. New partner user created with `role=partner`, `status=approved`
2. Password reset token generated (valid for 60 minutes)
3. Email sent to partner with password setup link
4. Partner must set password via email link before first login
5. No registration request needed - admin creates instantly

**Use Cases**:
- Partner sets their own password for security
- Email notification of account creation
- Self-service password setup
- Allows partner to choose password

---

**Errors** (Both Options):
- `422`: Email already exists, missing required fields, invalid password format
- `403`: Not authorized (non-admin user)
- `401`: No authentication token
- `500`: Server error during creation

**Password Requirements**:
- Minimum 8 characters
- Must match confirmation field
- Only required if you want immediate login

---

## Report Management Endpoints

### Report Workflow Overview

Partners create reports in three types:
- **Scanner**: Diagnostic scan data with kilometrage
- **Mechanic**: Mechanic inspection findings
- **Auto Body Technician**: Body and paint assessment

**Report Status Flow**: `draft` → `submitted` → `approved`/`rejected`

---

#### 11. Create New Report (Partner)
```http
POST /api/partner/reports
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json

{
  "vehicle_id": 1,
  "report_type": "scanner",
  "findings": {
    "engine_status": "good",
    "brake_system": "needs_maintenance",
    "error_codes": ["P0101", "P0102"]
  },
  "kilometrage": 125000,
  "payment_id": null
}
```

**Response (201)**:
```json
{
  "message": "Report created successfully",
  "report": {
    "id": 1,
    "vehicle_id": 1,
    "partner_id": 5,
    "report_type": "scanner",
    "findings": {
      "engine_status": "good",
      "brake_system": "needs_maintenance",
      "error_codes": ["P0101", "P0102"]
    },
    "kilometrage": 125000,
    "status": "draft",
    "risk_score": null,
    "report_date": "2026-02-14T10:00:00Z",
    "created_at": "2026-02-14T10:00:00Z"
  }
}
```

**Report Types**:
- `scanner`: Diagnostic data with kilometrage
- `mechanic`: Mechanic inspection findings
- `auto_body_technician`: Body and paint assessment

**Errors**:
- `403`: User is not a partner
- `422`: Validation failed (invalid vehicle_id, report_type, etc.)
- `400`: Invalid data format

---

#### 12. Get Partner's Reports
```http
GET /api/partner/reports?status=draft&page=1
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json
```

**Query Parameters**:
- `status`: Filter by status (draft, submitted, approved, rejected)
- `page`: Pagination (default: 1)

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "vehicle_id": 1,
      "partner_id": 5,
      "report_type": "scanner",
      "findings": {...},
      "kilometrage": 125000,
      "status": "draft",
      "risk_score": null,
      "report_date": "2026-02-14T10:00:00Z",
      "vehicle": {
        "id": 1,
        "vin": "WVW...",
        "brand": "Volkswagen",
        "model": "Golf",
        "year": 2019
      }
    }
  ],
  "current_page": 1,
  "total": 5,
  "per_page": 15
}
```

**Errors**:
- `403`: User is not a partner
- `401`: No authentication token

---

#### 13. Get Report Details
```http
GET /api/reports/1
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "id": 1,
  "vehicle_id": 1,
  "partner_id": 5,
  "report_type": "scanner",
  "findings": {
    "engine_status": "good",
    "brake_system": "needs_maintenance",
    "error_codes": ["P0101", "P0102"]
  },
  "kilometrage": 125000,
  "status": "submitted",
  "risk_score": 45,
  "pdf_path": "/reports/report_1.pdf",
  "report_date": "2026-02-14T10:00:00Z",
  "vehicle": {
    "id": 1,
    "vin": "WVW...",
    "brand": "Volkswagen",
    "model": "Golf",
    "year": 2019,
    "plate_number": "ABC-123"
  },
  "partner": {
    "id": 5,
    "name": "Amar Bouzida",
    "email": "amar@example.com"
  },
  "created_at": "2026-02-14T10:00:00Z"
}
```

**Errors**:
- `404`: Report not found
- `403`: Partner trying to view another partner's report
- `401`: No authentication token

---

#### 14. Update Draft Report (Partner)
```http
PUT /api/partner/reports/1
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json

{
  "report_type": "mechanic",
  "findings": {
    "engine_condition": "fair",
    "transmission": "good",
    "suspension": "needs_repair",
    "notes": "Ball joints worn out"
  },
  "kilometrage": 125050
}
```

**Response (200)**:
```json
{
  "message": "Report updated successfully",
  "report": {
    "id": 1,
    "vehicle_id": 1,
    "partner_id": 5,
    "report_type": "mechanic",
    "findings": {...},
    "kilometrage": 125050,
    "status": "draft",
    "updated_at": "2026-02-14T10:30:00Z"
  }
}
```

**Notes**:
- Can only update reports in `draft` status
- Once submitted, cannot update

**Errors**:
- `403`: User is not a partner or not owner of report
- `400`: Report is already submitted/approved
- `422`: Validation failed
- `404`: Report not found

---

#### 15. Submit Report (Partner)
```http
POST /api/partner/reports/1/submit
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "message": "Report submitted successfully",
  "report": {
    "id": 1,
    "status": "submitted",
    "updated_at": "2026-02-14T10:45:00Z"
  }
}
```

**What Happens**:
1. Report status changes from `draft` → `submitted`
2. Report becomes visible to admin for review
3. Partner can no longer edit the report

**Errors**:
- `403`: User is not a partner or not owner
- `400`: Report is not in draft status
- `404`: Report not found

---

#### 16. Delete Draft Report (Partner)
```http
DELETE /api/partner/reports/1
Authorization: Bearer PARTNER_TOKEN
Content-Type: application/json
```

**Response (200)**:
```json
{
  "message": "Report deleted successfully"
}
```

**Notes**:
- Can only delete reports in `draft` status
- Submitted reports cannot be deleted

**Errors**:
- `403`: User is not owner of report
- `400`: Report is not in draft status
- `404`: Report not found

---

#### 17. Get Pending Reports (Admin)
```http
GET /api/admin/reports/pending?page=1
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

**Query Parameters**:
- `page`: Pagination (default: 1)

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "vehicle_id": 1,
      "partner_id": 5,
      "report_type": "scanner",
      "findings": {...},
      "kilometrage": 125000,
      "status": "submitted",
      "risk_score": null,
      "report_date": "2026-02-14T10:00:00Z",
      "vehicle": {
        "id": 1,
        "vin": "WVW...",
        "brand": "Volkswagen",
        "model": "Golf",
        "year": 2019
      },
      "partner": {
        "id": 5,
        "name": "Amar Bouzida",
        "email": "amar@example.com"
      }
    }
  ],
  "current_page": 1,
  "total": 12,
  "per_page": 15
}
```

**Errors**:
- `403`: User is not an admin
- `401`: No authentication token

---

#### 18. Approve Report (Admin)
```http
POST /api/admin/reports/1/approve
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "risk_score": 35,
  "notes": "Report verified and approved"
}
```

**Response (200)**:
```json
{
  "message": "Report approved successfully",
  "report": {
    "id": 1,
    "status": "approved",
    "risk_score": 35,
    "updated_at": "2026-02-14T11:00:00Z"
  }
}
```

**Risk Score Range**: 0-100 (0 = no risk, 100 = high risk)

**Errors**:
- `403`: User is not an admin
- `400`: Report is not in submitted status
- `422`: Validation failed (risk_score out of range)
- `404`: Report not found

---

#### 19. Reject Report (Admin)
```http
POST /api/admin/reports/1/reject
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "rejection_reason": "Incomplete findings. Please provide more details on suspension system."
}
```

**Response (200)**:
```json
{
  "message": "Report rejected",
  "reason": "Incomplete findings. Please provide more details on suspension system.",
  "report": {
    "id": 1,
    "status": "rejected",
    "updated_at": "2026-02-14T11:00:00Z"
  }
}
```

**Notes**:
- Partner must create a new report (cannot resubmit rejected one)
- Rejection reason helps partner understand what to improve

**Errors**:
- `403`: User is not an admin
- `400`: Report is not in submitted status
- `422`: Rejection reason required
- `404`: Report not found

---

## Partner Registration Flow

### Complete User Journey

#### Step 1: Partner Submits Registration Request
```javascript
// Frontend: Submit partner registration form
const response = await fetch('http://localhost:8000/api/partner-registration-request', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: "Amar Bouzida",
    email: "amar@example.com",
    company_name: "VinCheck Partners",
    phone: "+213696451116",
    reason: "Professional inspector"
  })
});
```

#### Step 2: Admin Reviews Request
```javascript
// Frontend: Admin dashboard - view pending requests
const response = await fetch('http://localhost:8000/api/admin/partner-requests', {
  headers: { 'Authorization': `Bearer ${adminToken}` }
});
```

#### Step 3: Admin Approves Request
```javascript
// Frontend: Admin clicks approve button
const response = await fetch(
  `http://localhost:8000/api/admin/partner-requests/1/approve`,
  {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${adminToken}`,
      'Content-Type': 'application/json'
    }
  }
);
```

#### Step 4: Partner Receives Email
```
From: noreply@vincheck.com
To: amar@example.com
Subject: Welcome! Set Your Password - VinCheck

Email contains:
- Approval message
- "Set Your Password" button
- Link: http://localhost:3000/password-reset?token=XYZ&email=amar@example.com
- Warning: Link expires in 60 minutes
```

#### Step 5: Partner Sets Password
```javascript
// Frontend: Password reset page at /password-reset
// Extract token and email from URL
const params = new URLSearchParams(window.location.search);
const token = params.get('token');
const email = params.get('email');

// Submit password reset
const response = await fetch('http://localhost:8000/api/reset-password', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: email,
    token: token,
    password: "NewPassword123",
    password_confirmation: "NewPassword123"
  })
});
```

#### Step 6: Partner Login
```javascript
// Frontend: Login page
const response = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: "amar@example.com",
    password: "NewPassword123"
  })
});

const { token } = await response.json();
// Store token in localStorage
localStorage.setItem('authToken', token);
```

#### Step 7: Partner Accesses Partner Features
```javascript
// Frontend: Authenticated requests
const response = await fetch('http://localhost:8000/api/partner/dashboard', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## Implementation Checklist

### Frontend Pages to Create

- [ ] **Authentication Pages**
  - [ ] `/register` - Client registration form
  - [ ] `/login` - Login form
  - [ ] `/partner-join` - Partner registration request form
  - [ ] `/password-reset` - Password reset form (after email link)

- [ ] **Admin Dashboard**
  - [ ] `/admin/partner-requests` - List all pending requests with pagination
  - [ ] `/admin/partner-requests/:id` - View request details
  - [ ] Approve/Reject buttons on each request
  - [ ] `/admin/partners/create` - Create partner directly (new form)

- [ ] **Partner Dashboard**
  - [ ] `/partner/dashboard` - Partner home/overview
  - [ ] `/partner/profile` - Partner profile and settings
  - [ ] `/partner/reports` - List of partner's reports (draft, submitted, approved)
  - [ ] `/partner/reports/new` - Create new report form
  - [ ] `/partner/reports/:id` - View/edit report details
  - [ ] `/partner/reports/:id/edit` - Edit draft report
  - [ ] `/partner/reports/:id/submit` - Submit report confirmation

- [ ] **Admin Dashboard Reports**
  - [ ] `/admin/reports` - List all pending reports for review
  - [ ] `/admin/reports/:id` - View report details
  - [ ] `/admin/reports/:id/approve` - Approve report with risk score
  - [ ] `/admin/reports/:id/reject` - Reject report form

- [ ] **Client Dashboard**
  - [ ] `/client/dashboard` - Client home/overview
  - [ ] `/client/vehicles` - Vehicle management
  - [ ] `/client/requests` - Inspection requests

### Key Implementation Notes

1. **Token Storage**
   - Store token in localStorage or sessionStorage
   - Include in `Authorization: Bearer` header for all protected requests
   - Clear on logout

2. **Error Handling**
   - Handle 401 (unauthorized) → redirect to login
   - Handle 403 (forbidden) → show "access denied"
   - Handle 422 (validation) → show specific field errors
   - Handle 404 (not found) → show "not found"

3. **Password Reset Link**
   - Link format: `http://localhost:3000/password-reset?token=TOKEN&email=EMAIL`
   - Extract from URL query parameters
   - Valid for 60 minutes only
   - After reset, user status changes to `active`

4. **Pagination**
   - Admin partner requests use pagination (page, per_page parameters)
   - Default: 10 items per page

5. **Role-Based Conditional Rendering**
   - Check user role from login response
   - Show/hide UI based on `user.role` (admin, partner, client)
   - Enforce role restrictions on frontend AND backend

6. **Report Management**
   - Partners create reports in three types: scanner, mechanic, auto_body_technician
   - Reports start in `draft` status and can be edited
   - Once `submitted`, reports cannot be edited (show in review)
   - Admins review and either `approve` (with risk score) or `reject` (with reason)
   - Risk score range: 0-100 (0 = no risk, 100 = high risk)
   - Findings field accepts JSON object with detailed inspection data
   - Kilometrage is applicable mainly for scanner reports

7. **Report Data Structure**
   ```javascript
   // Example Scanner Report
   {
     vehicle_id: 1,
     report_type: "scanner",
     kilometrage: 125000,
     findings: {
       engine_status: "good",
       brake_system: "needs_maintenance",
       error_codes: ["P0101", "P0102"], 
       last_service_date: "2025-06-15"
     }
   }

   // Example Mechanic Report
   {
     vehicle_id: 2,
     report_type: "mechanic",
     findings: {
       engine_condition: "fair",
       transmission: "good",
       suspension: "needs_repair",
       notes: "Ball joints worn out"
     }
   }

   // Example Auto Body Technician Report
   {
     vehicle_id: 3,
     report_type: "auto_body_technician",
     findings: {
       body_condition: "good",
       paint_condition: "fair",
       rust_spots: "minor",
       previous_repairs: "hood and right fender",
       notes: "Recent repaint, quality appears good"
     }
   }
   ```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| `200` | Success | Use response data |
| `201` | Created | Resource created successfully |
| `400` | Bad Request | Show user-friendly error message |
| `401` | Unauthorized | Redirect to login |
| `403` | Forbidden | Show "access denied" |
| `404` | Not Found | Show "not found" message |
| `422` | Validation Error | Show field-specific errors |
| `500` | Server Error | Show "server error" message |

### Error Response Format
```json
{
  "message": "User friendly error message",
  "errors": {
    "email": ["Email already exists"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

### Frontend Error Handling Example
```javascript
try {
  const response = await fetch(url, options);
  
  if (!response.ok) {
    if (response.status === 401) {
      // Redirect to login
      window.location.href = '/login';
    } else if (response.status === 422) {
      // Show validation errors
      const { errors } = await response.json();
      console.log(errors);
    } else {
      const { message } = await response.json();
      alert(message);
    }
    return;
  }
  
  return await response.json();
} catch (error) {
  console.error('Network error:', error);
  alert('Network error. Please try again.');
}
```

---

## Email Templates

### Partner Approval Email

**Sent When**: Admin approves a partner request

**Email Details**:
- From: `noreply@vincheck.com` (VinCheck)
- To: Partner's email address
- Subject: `Welcome! Set Your Password - VinCheck`

**Email Content**:
```
Hello [Partner Name],

Great news! Your VinCheck partner account has been approved!

To complete your registration and access your account, please set 
your password by clicking the button below:

[SET YOUR PASSWORD BUTTON]
http://localhost:3000/password-reset?token=XYZ&email=partner@example.com

Important: This link will expire in 60 minutes for security.

If you didn't request this, please ignore this email.

© 2026 VinCheck. All rights reserved.
```

**Frontend Action**:
- User clicks link
- Extracts token and email from URL
- Shows password reset form
- Submits to `/api/reset-password`
- Redirects to login after success

---

## Database Schema Reference

### Users Table
```sql
- id: bigint (primary key)
- name: string
- email: string (unique)
- password: string (nullable - for approved but not activated users)
- role: enum (admin, partner, client)
- status: enum (active, approved, suspended)
- email_verified_at: timestamp (nullable)
- created_at: timestamp
- updated_at: timestamp
```

### Registration Requests Table
```sql
- id: bigint (primary key)
- name: string
- email: string (unique)
- company_name: string
- phone: string
- reason: text
- status: enum (pending, approved, rejected)
- created_at: timestamp
- updated_at: timestamp
```

### Password Reset Tokens Table
```sql
- email: string (primary key)
- token: string
- created_at: timestamp (nullable)
```

---

## Environment Configuration

### .env File (Backend)
```env
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000
DB_DATABASE=car_check
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=amarbouzida62@gmail.com
MAIL_FROM_ADDRESS=amarbouzida62@gmail.com
```

### Frontend Configuration
```javascript
// .env.local
REACT_APP_API_URL=http://localhost:8000/api
```

---

## Testing Guide

### Manual Testing Steps

1. **Client Registration**
   ```
   POST http://localhost:8000/api/register
   → Should get token back
   → Status should be 'active'
   ```

2. **Partner Application**
   ```
   POST http://localhost:8000/api/partner-registration-request
   → Should get request ID
   → Status should be 'pending'
   ```

3. **Admin Approval**
   ```
   POST http://localhost:8000/api/admin/partner-requests/1/approve
   → Should create user with status='approved'
   → Should send email to partner
   ```

4. **Check Email**
   ```
   Login to Gmail (amarbouzida62@gmail.com)
   → Should see approval email with password reset link
   ```

5. **Password Reset**
   ```
   GET http://localhost:3000/password-reset?token=XYZ&email=...
   → Should show password form
   → POST /api/reset-password
   → Should change status to 'active'
   ```

6. **Login as Partner**
   ```
   POST /api/login with partner email and new password
   → Should get token back
   → Status should be 'active'
   ```

---

## Support & Questions

For any questions about integration:
- Check the API response format
- Verify authentication token is included
- Check browser console for errors
- Check backend logs: `storage/logs/laravel.log`
- Ensure .env configuration is correct
- Verify database migrations ran: `php artisan migrate`

---

**Last Updated**: February 14, 2026  
**Backend Framework**: Laravel 12  
**API Version**: 1.0
