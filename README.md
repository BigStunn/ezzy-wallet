# eZZY Wallet

**A custom WordPress manual wallet management plugin for account registration, deposits, withdrawals, and administrative transaction processing.**

## Overview

eZZY Wallet is a custom WordPress plugin developed to provide a manual digital wallet system. It allows users to create accounts, submit deposit requests using their preferred payment method, maintain wallet balances, and request withdrawals.

Unlike an automated payment gateway, eZZY Wallet uses a manual payment verification workflow. The administrator provides the relevant payment account details, verifies incoming payments, and updates user wallet balances accordingly.

## Key Features

- **User account registration:** Allows users to create accounts and submit their information.
- **Deposit requests:** Users can submit deposit requests and specify the amount they wish to add to their wallet.
- **Multiple payment methods:** Supports configurable payment options, potentially including PayPal, Bitcoin, and other methods accepted by the administrator.
- **Manual payment processing:** Payments are completed outside the automated wallet workflow, with the administrator handling payment instructions and verification.
- **Wallet balance management:** Enables users to maintain funds in their wallet accounts.
- **Withdrawal requests:** Allows users to request withdrawals from their wallet balances.
- **Administrative management:** Provides an admin-managed process for reviewing transactions and updating wallet balances.

## Technologies

- WordPress
- PHP
- Database-backed user and wallet data management
- Custom WordPress plugin development

## Payment Workflow

1. A user registers for an account.
2. The user submits a deposit request, specifying the amount and preferred payment method.
3. The administrator provides the relevant payment account details.
4. The user makes the payment outside the automated wallet workflow.
5. The administrator verifies the payment and updates the user's wallet balance.

## Project Purpose

eZZY Wallet was developed to provide a flexible manual wallet system for websites that need account balances, deposit requests, and withdrawals without requiring every payment to be processed automatically through a payment gateway.

## Developer

Bryant Macwhite Akonjang

WordPress Developer | Custom Plugin Development

## Important Notes

eZZY Wallet manages a manual payment workflow. Selecting a payment method does not itself process or verify a payment automatically. Actual payment verification and wallet balance updates depend on the administrator's workflow and the plugin's implemented functionality.
