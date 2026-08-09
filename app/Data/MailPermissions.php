<?php

namespace App\Data;

readonly class MailPermissions
{
    public function __construct(
        public bool $canManageProviders,
        public bool $canManageDomains,
        public bool $canSendEmail,
        public bool $canViewEmails,
        public bool $canManageTemplates,
        public bool $canManageSuppressions,
        public bool $canManageApiKeys,
    ) {
        //
    }
}
