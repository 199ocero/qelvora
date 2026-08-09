<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::UpdateTeam);
    }

    /**
     * Determine whether the user can leave the team.
     */
    public function leave(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && $user->belongsToTeam($team)
            && ! $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can add a member to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::AddMember);
    }

    /**
     * Determine whether the user can update a member's role in the team.
     */
    public function updateMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::UpdateMember);
    }

    /**
     * Determine whether the user can remove a member from the team.
     */
    public function removeMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::RemoveMember);
    }

    /**
     * Determine whether the user can invite members to the team.
     */
    public function inviteMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CreateInvitation);
    }

    /**
     * Determine whether the user can cancel invitations.
     */
    public function cancelInvitation(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CancelInvitation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return ! $team->is_personal && $user->hasTeamPermission($team, TeamPermission::DeleteTeam);
    }

    /**
     * Determine whether the user can manage the team's email provider connections.
     */
    public function manageProviders(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageMailProviders);
    }

    /**
     * Determine whether the user can manage the team's sending domains/identities.
     */
    public function manageDomains(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageMailDomains);
    }

    /**
     * Determine whether the user can send email on behalf of the team.
     */
    public function sendEmail(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::SendEmail);
    }

    /**
     * Determine whether the user can view the team's email logs and analytics.
     */
    public function viewEmails(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ViewEmails);
    }

    /**
     * Determine whether the user can manage the team's email templates.
     */
    public function manageTemplates(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageMailTemplates);
    }

    /**
     * Determine whether the user can manage the team's suppression list.
     */
    public function manageSuppressions(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSuppressions);
    }

    /**
     * Determine whether the user can manage the team's API keys.
     */
    public function manageApiKeys(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageApiKeys);
    }
}
