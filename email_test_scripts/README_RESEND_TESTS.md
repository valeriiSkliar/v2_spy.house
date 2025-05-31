# Resend Contact Management Tests

This directory contains test scripts to verify the Resend contact management functionality implemented in the application.

## Test Scripts

### 1. `test_resend_contact_management.php`
**Purpose**: Tests basic Resend API operations using the official Laravel package.

**What it tests**:
- ✅ Creating new contacts
- ✅ Getting contacts by email address  
- ✅ Updating existing contacts
- ✅ Email change scenarios
- ✅ Non-existent contact handling
- ✅ Cleanup operations

### 2. `test_user_email_workflow.php`  
**Purpose**: Simulates the complete user email verification and change workflow.

**What it tests**:
- ✅ New user email verification flow (EmailVerificationController)
- ✅ User email change flow (ProfileController) 
- ✅ Re-verification scenarios (no duplicate creation)
- ✅ Data consistency checks
- ✅ Proper contact ID tracking

### 3. `run_resend_tests.php`
**Purpose**: Test runner that executes all test scripts and provides timing information.

## Prerequisites

Before running the tests, ensure you have:

1. **Environment Configuration**:
   ```env
   RESEND_API_KEY=your_resend_api_key
   RESEND_AUDIENCE_ID=your_audience_id
   ```

2. **Laravel Dependencies**: Make sure the Resend Laravel package is properly installed:
   ```bash
   composer require resend/resend-laravel
   ```

3. **Configuration**: Verify your `config/services.php` contains:
   ```php
   'resend' => [
       'key' => env('RESEND_API_KEY'),
       'audience_id' => env('RESEND_AUDIENCE_ID'),
   ],
   ```

## Running the Tests

### Run All Tests
```bash
php email_test_scripts/run_resend_tests.php
```

### Run Individual Tests
```bash
# Basic API functionality test
php email_test_scripts/test_resend_contact_management.php

# User workflow simulation
php email_test_scripts/test_user_email_workflow.php
```

## What the Tests Validate

### Problem Prevention
These tests specifically validate that the implemented solution prevents:
- ❌ **Duplicate contacts** when users re-verify their email
- ❌ **Orphaned contacts** when users change their email address
- ❌ **Data inconsistency** between application database and Resend

### Workflow Validation
1. **Email Verification Flow**:
   - Check if contact exists in Resend
   - Create new contact OR update existing contact  
   - Update user record with `email_contact_id` and subscription status

2. **Email Change Flow**:
   - Update Resend contact with new email address
   - Maintain same contact ID (no duplication)
   - Update user record if contact ID changes

3. **Re-verification Flow**:
   - Find existing contact by email
   - Update contact information (no new contact creation)
   - Maintain data consistency

## Expected Output

### ✅ Success Indicators
- All contacts created/updated successfully
- No duplicate contacts created
- Email changes properly reflected in Resend
- Contact IDs properly tracked and updated
- Cleanup operations successful

### ❌ Failure Indicators  
- API key or audience ID missing/invalid
- Duplicate contacts created during re-verification
- Email changes creating new contacts instead of updating
- Contact IDs not properly tracked
- Cleanup failures (manual intervention needed)

## Troubleshooting

### Common Issues

1. **Missing Configuration**:
   ```
   ❌ Missing required configuration. Please set RESEND_API_KEY and RESEND_AUDIENCE_ID
   ```
   **Solution**: Add the required environment variables to your `.env` file.

2. **API Errors**:
   ```
   ❌ Exception when creating contact: Invalid API key
   ```
   **Solution**: Verify your Resend API key is correct and has the necessary permissions.

3. **Audience Not Found**:
   ```
   ❌ Exception when creating contact: Audience not found
   ```
   **Solution**: Verify your audience ID exists in your Resend account.

### Manual Cleanup

If tests fail to cleanup automatically, you may need to manually remove test contacts from your Resend audience. Test emails follow the pattern:
- `test-{timestamp}@example.com`
- `updated-{timestamp}@example.com`
- `new-user-{timestamp}@example.com`

## Integration with Application Code

These tests validate the functionality implemented in:

- **EmailVerificationController**: `app/Http/Controllers/Auth/EmailVerificationController.php`
- **ProfileController**: `app/Http/Controllers/Frontend/Profile/ProfileController.php`

The tests use the same Resend facade and methods as the application code, ensuring accurate validation of the production implementation.