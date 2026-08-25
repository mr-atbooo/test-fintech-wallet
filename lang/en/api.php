<?php

return [

    'auth' => [
        'registered' => 'Registration successful. Please verify the OTP code sent to you.',
        'login_success' => 'Login successful.',
        'logout_success' => 'Logged out successfully.',
        'token_refreshed' => 'Token refreshed successfully.',
        'me_fetched' => 'Current user fetched successfully.',
        'otp_sent' => 'OTP code sent successfully.',
        'otp_verified' => 'OTP verified successfully.',
        'password_reset_success' => 'Password has been reset successfully.',
        'invalid_credentials' => 'Invalid email/phone or password.',
        'account_inactive' => 'Your account is inactive.',
        'account_blocked' => 'Your account has been blocked.',
        'account_pending' => 'Your account is pending verification. Please verify the OTP sent to you.',
        'invalid_otp' => 'Invalid or expired OTP code.',
        'invalid_refresh_token' => 'Invalid or expired refresh token.',
        'user_not_found' => 'No account found with the given email or phone.',
    ],

    'wallet' => [
        'created' => 'Wallet created successfully.',
        'updated' => 'Wallet updated successfully.',
        'deleted' => 'Wallet deleted successfully.',
        'fetched' => 'Wallets fetched successfully.',
        'has_balance' => 'Cannot delete a wallet with a non-zero balance.',
        'insufficient_balance' => 'This operation would result in a negative wallet balance.',
    ],

    'transaction' => [
        'created' => 'Transaction created successfully.',
        'updated' => 'Transaction updated successfully.',
        'deleted' => 'Transaction deleted successfully.',
        'fetched' => 'Transactions fetched successfully.',
        'category_type_mismatch' => 'The selected category type does not match the transaction type.',
        'transfer_linked' => 'This transaction is part of a transfer and cannot be modified or deleted directly.',
    ],

    'transfer' => [
        'created' => 'Transfer completed successfully.',
        'fetched' => 'Transfers fetched successfully.',
    ],

    'category' => [
        'fetched' => 'Categories fetched successfully.',
    ],

    'dashboard' => [
        'summary_fetched' => 'Dashboard summary fetched successfully.',
        'chart_fetched' => 'Dashboard chart fetched successfully.',
    ],

    'notification' => [
        'fetched' => 'Notifications fetched successfully.',
        'marked_read' => 'Notification marked as read.',
        'all_marked_read' => 'All notifications marked as read.',
        'transaction_income_title' => 'New Income',
        'transaction_expense_title' => 'New Expense',
        'transaction_body' => ':amount :currency in :wallet',
        'low_balance_title' => 'Low Balance Warning',
        'low_balance_body' => 'Your wallet ":wallet" balance is now :balance :currency.',
        'transfer_title' => 'Transfer Completed',
        'transfer_body' => ':amount :currency from :from to :to',
    ],

    'errors' => [
        'validation_failed' => 'The given data was invalid.',
        'unauthenticated' => 'Unauthenticated.',
        'forbidden' => 'This action is unauthorized.',
        'not_found' => 'The requested resource was not found.',
        'server_error' => 'Something went wrong. Please try again later.',
        'too_many_requests' => 'Too many requests. Please slow down.',
    ],

];
