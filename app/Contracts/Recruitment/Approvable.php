<?php

namespace App\Contracts\Recruitment;

interface Approvable
{
    /**
     * The document_type string used in approval workflows/records.
     */
    public function approvalDocumentType(): string;

    /**
     * Status to set when the document is fully approved.
     */
    public function approvedStatus(): string;

    /**
     * Status to set when the document is rejected.
     */
    public function rejectedStatus(): string;

    /**
     * Status to set when the document is submitted for approval.
     */
    public function submittedStatus(): string;

    /**
     * Status to set when the document reverts to draft (e.g. after rejection).
     */
    public function draftStatus(): string;

    /**
     * Callback invoked after full approval. Override for side effects.
     */
    public function onFullyApproved(): void;

    /**
     * Callback invoked after rejection. Override for side effects.
     */
    public function onRejected(): void;
}
