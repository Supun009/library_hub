# Member Suspension System

## Overview

The library system includes automatic member suspension for members with long overdue books, plus manual controls to prevent deactivation/deletion of members with unreturned books.

## Features

### 1. **Block Deactivation/Deletion with Unreturned Books**

#### What It Does:

- Prevents admins from deactivating members who have unreturned books
- Prevents admins from deleting members who have unreturned books
- Shows warning message with count of unreturned books
- Displays overdue book count if applicable

#### How It Works:

When viewing a member's edit page (`admin/edit_member.php`):

- System checks for unreturned books
- If books are unreturned:
  - Deactivate button is disabled
  - Delete button is disabled
  - Tooltip shows reason
  - Warning banner displays book counts

#### User Experience:

```
┌─────────────────────────────────────┐
│ ⚠️  3 Unreturned Book(s)            │
│    1 Overdue                        │
└─────────────────────────────────────┘

[Deactivate Account] ← Disabled (grayed out)
  Hover: "Cannot deactivate. Member has 3 unreturned book(s)."
```

---

### 2. **Auto-Suspend Members with Long Overdue Books**

#### What It Does:

- Automatically suspends members with books overdue for more than 30 days
- Runs as a scheduled task (cron job)
- Logs all suspensions for audit trail

#### Configuration:

Edit `scripts/auto_suspend_members.php`:

```php
$OVERDUE_DAYS_THRESHOLD = 30;  // Days before auto-suspend
$DRY_RUN = false;              // Set true to test without changes
```

#### How to Run:

**Manual Execution:**

```bash
cd e:\xampp\htdocs\lib_system\library_system\scripts
php auto_suspend_members.php
```

**Scheduled Execution (Windows Task Scheduler):**

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Library Auto-Suspend Members"
4. Trigger: Daily at 2:00 AM
5. Action: Start a program
   - Program: `E:\xampp\php\php.exe`
   - Arguments: `E:\xampp\htdocs\lib_system\library_system\scripts\auto_suspend_members.php`

**Scheduled Execution (Linux Cron):**

```bash
# Run daily at 2:00 AM
0 2 * * * php /path/to/library_system/scripts/auto_suspend_members.php
```

#### Output Example:

```
Found 2 member(s) to suspend:
--------------------------------------------------------------------------------
ID: 5 | Name: John Doe | Overdue Books: 2 | Days Overdue: 45
  → SUSPENDED
ID: 12 | Name: Jane Smith | Overdue Books: 1 | Days Overdue: 35
  → SUSPENDED
--------------------------------------------------------------------------------
Successfully suspended 2 member(s).
```

---

### 3. **Prevent Suspended Members from Borrowing**

#### What It Does:

- Checks member status before issuing books
- Only active members can borrow books
- Clear error message if member is suspended

#### How It Works:

When issuing a book (`admin/issue_book.php`):

1. Admin selects member and books
2. System checks member status
3. If member is inactive/suspended:
   - Transaction is blocked
   - Error message displayed
   - Books remain available

#### Error Message:

```
❌ Cannot issue books. Member account is inactive/suspended.
   Please activate the account first.
```

---

## Workflow Example

### Scenario: Member with Overdue Books

**Day 1-14:** Normal borrowing period

- Member has book, no issues

**Day 15-29:** Book is overdue

- Member can still borrow (warning shown)
- Fines may accumulate
- Member status: **Active**

**Day 30+:** Auto-suspension triggers

- Cron job runs at 2:00 AM
- Member is automatically suspended
- Member status: **Inactive**
- Member cannot borrow new books

**Admin Actions:**

- Cannot deactivate (already inactive)
- Cannot delete (has unreturned books)
- Must wait for book return

**Book Return:**

- Member returns overdue books
- Admin can now:
  - Reactivate member account
  - Or delete member if needed

---

## Database Schema

### Members Table

```sql
members
├── member_id
├── status ('active' or 'inactive')
└── deleted_at (soft delete)
```

### Issues Table

```sql
issues
├── issue_id
├── member_id
├── book_id
├── issue_date
├── due_date
└── return_date (NULL if not returned)
```

---

## Best Practices

### For Administrators:

1. **Regular Monitoring**
   - Check suspended members weekly
   - Contact members with overdue books
   - Review auto-suspension logs

2. **Before Deactivating**
   - Ensure all books are returned
   - Check for outstanding fines
   - Verify member has no pending transactions

3. **Reactivation Process**
   - Confirm all books returned
   - Collect any outstanding fines
   - Update member status to 'active'

### For System Maintenance:

1. **Cron Job Setup**
   - Set up auto-suspend script to run daily
   - Monitor script execution logs
   - Adjust threshold as needed

2. **Testing**
   - Use `$DRY_RUN = true` to test changes
   - Verify suspension logic with test data
   - Check email notifications (if implemented)

3. **Customization**
   - Adjust `OVERDUE_DAYS_THRESHOLD` for your policy
   - Add email notifications (optional)
   - Customize suspension criteria

---

## Configuration Options

### Suspension Threshold

```php
// In scripts/auto_suspend_members.php
$OVERDUE_DAYS_THRESHOLD = 30;  // Change to your preference
```

**Recommended Values:**

- **Strict:** 14 days
- **Standard:** 30 days
- **Lenient:** 60 days

### Dry Run Mode

```php
$DRY_RUN = true;  // Test mode - no changes made
$DRY_RUN = false; // Production mode - actually suspend members
```

---

## Troubleshooting

### Members Not Auto-Suspending

**Check:**

1. Is cron job running?
   ```bash
   # Check Windows Task Scheduler or Linux crontab
   ```
2. Is `$DRY_RUN` set to `false`?
3. Are members actually overdue for threshold days?

### Cannot Deactivate Member

**This is by design!**

- Member has unreturned books
- Return books first, then deactivate

### Suspended Member Trying to Borrow

**Expected behavior:**

- System blocks the transaction
- Show error message
- Admin must reactivate account first

---

## Future Enhancements

Potential improvements:

- [ ] Email notifications to suspended members
- [ ] SMS alerts for overdue books
- [ ] Graduated suspension (warning → suspension)
- [ ] Auto-reactivation after book return
- [ ] Fine calculation integration
- [ ] Suspension appeal system

---

## Security Considerations

1. **Audit Trail**
   - All suspensions are logged
   - Track who activated/deactivated members
   - Monitor admin actions

2. **Data Integrity**
   - Cannot delete members with active loans
   - Soft delete preserves history
   - Transaction records maintained

3. **Access Control**
   - Only admins can suspend/activate
   - Member status changes logged
   - Prevent unauthorized modifications

---

## Support

For issues or questions:

1. Check this documentation
2. Review script output/logs
3. Verify database queries
4. Contact system administrator

---

**Last Updated:** 2026-02-05
**Version:** 1.0
