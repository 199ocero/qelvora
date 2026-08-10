export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: {
        current_page: number;
        last_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    current_page?: number;
    last_page?: number;
    total?: number;
};

export type MailProvider = 'ses';

export type ProviderConnectionStatus = 'pending' | 'connected' | 'failed';

export type IdentityType = 'domain' | 'email';

export type IdentityStatus =
    'not_started' | 'pending' | 'verified' | 'failed' | 'temporary_failure';

export type EmailMessageStatus =
    | 'scheduled'
    | 'queued'
    | 'sent'
    | 'delivered'
    | 'bounced'
    | 'complained'
    | 'rejected'
    | 'failed';

export type EmailEventType =
    | 'send'
    | 'delivery'
    | 'bounce'
    | 'complaint'
    | 'open'
    | 'click'
    | 'reject'
    | 'rendering_failure'
    | 'delivery_delay';

export type SuppressionReason = 'bounce' | 'complaint' | 'manual';

export type SuppressionSource = 'provider' | 'local' | 'event';

export type CredentialField = {
    name: string;
    label: string;
    type: 'text' | 'password' | 'select';
    required: boolean;
    placeholder?: string;
    help?: string;
    options?: { value: string; label: string }[];
};

export type ProviderOption = {
    value: MailProvider;
    label: string;
    implemented: boolean;
    capabilities: string[];
    credentialFields: CredentialField[];
};

export type ProviderConnection = {
    id: number;
    provider: MailProvider;
    providerLabel: string;
    status: ProviderConnectionStatus;
    isActive: boolean;
    productionAccess: boolean;
    sendQuotaMax: number | null;
    sentLast24h: number | null;
    maxSendRate: number | null;
    enforcementStatus: string | null;
    bounceRate: number | null;
    complaintRate: number | null;
    webhookProvisioned: boolean;
    provisionError: string | null;
    connectedAt: string | null;
    lastSyncedAt: string | null;
};

export type DnsRecordStatus = 'seen' | 'missing';

export type DnsRecord = {
    type: string;
    host: string;
    value: string;
    priority: number | null;
    purpose: string | null;
    status: DnsRecordStatus | null;
};

export type MailIdentity = {
    id: number;
    identity: string;
    type: IdentityType;
    status: IdentityStatus;
    statusLabel: string;
    dnsRecords: DnsRecord[];
    verifiedAt: string | null;
    lastCheckedAt: string | null;
    createdAt: string;
};

export type EmailMessage = {
    id: number;
    provider: MailProvider;
    providerMessageId: string | null;
    fromAddress: string;
    to: string[];
    subject: string | null;
    html?: string | null;
    text?: string | null;
    status: EmailMessageStatus;
    statusLabel: string;
    sentVia: string;
    opensCount: number;
    clicksCount: number;
    error: string | null;
    lastEventAt: string | null;
    scheduledAt: string | null;
    createdAt: string;
};

export type EmailTemplate = {
    id: number;
    name: string;
    slug: string;
    subject: string | null;
    html?: string | null;
    text?: string | null;
    variables: string[];
    createdAt: string;
    updatedAt: string;
};

export type EmailLogFilters = {
    search: string | null;
    status: string | null;
    via: string | null;
    from: string | null;
    to: string | null;
};

export type EmailEvent = {
    id: number;
    type: EmailEventType;
    typeLabel: string;
    bounceType: string | null;
    complaintType: string | null;
    occurredAt: string | null;
    createdAt: string;
};

export type Suppression = {
    id: number;
    email: string;
    reason: SuppressionReason;
    reasonLabel: string;
    source: SuppressionSource;
    notes: string | null;
    suppressedAt: string | null;
    createdAt: string;
};

export type ApiKey = {
    id: number;
    name: string;
    keyPrefix: string;
    lastFour: string;
    restrictedTo: string | null;
    createdBy: string | null;
    lastUsedAt: string | null;
    revokedAt: string | null;
    createdAt: string;
};

export type MailPermissions = {
    canManageProviders: boolean;
    canManageDomains: boolean;
    canSendEmail: boolean;
    canViewEmails: boolean;
    canManageTemplates: boolean;
    canManageSuppressions: boolean;
    canManageApiKeys: boolean;
};
