<?php

namespace App\Services\Ingestion;

use Illuminate\Http\Request;
use InvalidArgumentException;

final class AuthenticatedIngressRejectionRecorder
{
    public function __construct(private readonly IngressRolloutService $rollout) {}

    public function record(
        string $path,
        AuthenticatedDeviceContext $authenticated,
        Request $request,
        string $reasonCode,
        int $status,
    ): void {
        if ($authenticated->dataLogger->id < 1 || $authenticated->monitoringStationId < 1) {
            throw new InvalidArgumentException('Authenticated rejection evidence requires a trusted source context.');
        }

        $reasonCodes = config('canonical.ingestion.rejection_reason_codes', []);
        $reasonCode = in_array($reasonCode, $reasonCodes, true)
            ? $reasonCode
            : 'validation_failed';
        $statuses = config('canonical.ingestion.rejection_status_codes', []);
        $status = in_array($status, $statuses, true) ? $status : 422;
        $body = $request->getContent();

        $this->rollout->recordEvidence($path, [
            'capture_outcome' => 'rejected',
            'reason_code' => substr($reasonCode.':'.$status, 0, 100),
            'payload_size' => strlen($body),
            'payload_sha256' => hash('sha256', $body),
        ]);
    }
}
