<?php

namespace App\Contracts;

interface AiAssistantInterface
{
    /**
     * Set the user executing the action and optional team context.
     */
    public function forUser(\App\Models\User $user, ?int $teamId = null): self;
    
    /**
     * Set the task context for the next generation.
     */
    public function withTaskContext(\App\Models\Task $task): self;

    /**
     * Set an attachment context for the next generation.
     */
    public function withAttachmentContext(\App\Models\TaskAttachment $attachment): self;
    
    /**
     * Set the forum context (thread and optional message context).
     */
    public function withForumContext(\App\Models\ForumThread $thread, ?\App\Models\ForumMessage $message = null): self;

    /**
     * Generate text based on a prompt.
     */
    public function generateText(string $prompt): string;

    /**
     * Analyze user data (like task history) to get an energy/mood score.
     */
    public function analyzeEnergyLevel(array $recentData): int;

    /**
     * Translate or simplify a complex text (Complexity Translator).
     */
    public function simplifyText(string $complexText): string;
}
