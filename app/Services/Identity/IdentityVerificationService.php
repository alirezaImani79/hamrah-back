<?php

namespace App\Services\Identity;

use App\Enums\IdentityVerificationStatus;
use App\Jobs\VerifyUserIdentity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IdentityVerificationService
{
    /**
     * Store the submitted identity data and documents, mark the user as
     * "verifying", and queue the automated verification job.
     *
     * @param  array{first_name: string, last_name: string, national_code: string, birth_date: string, gender: string, province_id: int, city_id: int, address: string}  $data
     */
    public function submit(User $user, array $data, UploadedFile $nationalCardImage, UploadedFile $faceImage): User
    {
        $this->deletePreviousDocuments($user);

        $directory = 'identity/'.$user->getKey();
        $disk = $this->disk();

        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_code' => $data['national_code'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'address' => $data['address'],
            'national_card_image_path' => $nationalCardImage->store($directory, $disk),
            'face_image_path' => $faceImage->store($directory, $disk),
            'identity_status' => IdentityVerificationStatus::Verifying,
            'identity_verified_at' => null,
            'identity_verification_result' => null,
        ])->save();

        VerifyUserIdentity::dispatch($user);

        return $user;
    }

    /**
     * Remove any documents from a previous submission so re-submissions do not
     * leave orphaned files behind.
     */
    private function deletePreviousDocuments(User $user): void
    {
        $paths = array_filter([$user->national_card_image_path, $user->face_image_path]);

        if ($paths !== []) {
            Storage::disk($this->disk())->delete($paths);
        }
    }

    private function disk(): string
    {
        return (string) config('identity.disk');
    }
}
