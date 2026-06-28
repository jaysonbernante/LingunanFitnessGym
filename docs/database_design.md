# Database Design

This document describes the relational database design for the Lingunan Fitness Gym Web-Based Gym Management System with RFID-Based Membership.

The database is designed to support membership management, RFID access control, fitness entry tracking, staff administration, product sales, and wallet transactions. It is based on the current system schema and existing application tables.

## Overview

The database is normalized to minimize data redundancy and preserve data integrity. It uses InnoDB tables with foreign key relationships for referential integrity.

### Table Summary

Each table is listed with its purpose and how it supports the Lingunan Fitness Gym system.

### `users`

Purpose: Stores system accounts for administrators and staff.

Why it exists: This table provides authentication and role-based access for the gym management system, allowing staff and admins to sign in and manage operations.

### `members`

Purpose: Stores gym member data, membership details, login credentials, and RFID identifiers.

Why it exists: This table is the core member registry. It tracks membership status, RFID cards, user login details, membership dates, and wallet credit for each member.

### `products`

Purpose: Stores merchandise and products sold by the gym.

Why it exists: This table manages the gym store inventory, keeping product names, quantities, prices, and stock dates for retail operations.

### `sales`

Purpose: Records each product sale transaction.

Why it exists: This table stores sale history, payment methods, and transaction metadata so the gym can track retail revenue and order details.

### `wallet_transactions`

Purpose: Tracks wallet and credit activity for gym members.

Why it exists: This table records wallet top-ups, deductions, and balance changes so member credit history is auditable and consistent.

### `entry_logs`

Purpose: Captures fitness center access and gym entry events.

Why it exists: This table logs entries for members and walk-ins, including session payments, membership entries, and RFID access times.

### `blocked_rfids`

Purpose: Stores RFID tags that are suspended or blocked.

Why it exists: This table supports RFID security by tracking lost or invalid cards and preventing unauthorized access.

## Relationships Summary

- `users` -> `members`: one-to-many. A staff user may manage multiple member records through the system.
- `members` -> `wallet_transactions`: one-to-many. A member can have multiple wallet activity records.
- `members` -> `entry_logs`: one-to-many. A member can generate many entry events.
- `products` -> `sales`: one-to-many. Each product can appear in multiple sales.
- `members` -> `blocked_rfids`: one-to-many (optional). A member may have multiple blocked RFID records.

## Normalization

The schema follows a normalized model where each entity is stored in a dedicated table and related records use foreign keys. Historical transaction details are retained in related tables while member and product data remain central.

## Diagram Notes

A conceptual ERD for the system would show:

- `users` connected to `members`
- `members` connected to `wallet_transactions`
- `members` connected to `entry_logs`
- `members` connected to `blocked_rfids`
- `products` connected to `sales`

## Usage in the System

- `members`, `entry_logs`, and `blocked_rfids` support RFID-based gym access.
- `wallet_transactions` supports member credit and payment tracking.
- `products` and `sales` support gym retail and ecommerce operations.
- `users` supports staff/admin access and role control.

## Implementation Details

This design is based on the current schema from `dbgym.sql` and reflects the tables already present in the Lingunan Fitness Gym system.

If desired, the next step is to generate a graphical ERD using a tool such as MySQL Workbench or dbdiagram.io from this schema.
