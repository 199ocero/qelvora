<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ManageMailProviders = 'mail:manage';
    case ManageMailDomains = 'mail:domain:manage';
    case SendEmail = 'mail:email:send';
    case ViewEmails = 'mail:email:view';
    case ManageMailTemplates = 'mail:template:manage';
    case ManageSuppressions = 'mail:suppression:manage';
    case ManageApiKeys = 'mail:apikey:manage';
}
