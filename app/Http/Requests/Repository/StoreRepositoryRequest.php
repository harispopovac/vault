<?php

namespace App\Http\Requests\Repository;

use App\Models\Repository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepositoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'regex:/^https?:\/\/github\.com\/[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_\.]+\/?$/',
                function ($attribute, $value, $fail) {
                    // Parse and validate GitHub URL format
                    $repoInfo = $this->parseGitHubUrl($value);
                    if (!$repoInfo) {
                        $fail('The URL must be a valid GitHub repository URL in the format: https://github.com/owner/repository');
                        return;
                    }
                    
                    // Check if repository is already linked
                    $existingRepo = Repository::where('name', $repoInfo['fullName'])->first();
                    if ($existingRepo) {
                        $fail('This repository is already linked.');
                        return;
                    }
                },
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Repository URL is required',
            'url.url' => 'Please enter a valid URL',
            'url.regex' => 'Please enter a valid GitHub repository URL in the format: https://github.com/username/repository',
        ];
    }

    /**
     * Parse GitHub URL and extract repository information
     */
    private function parseGitHubUrl(string $url): ?array
    {
        try {
            $parsedUrl = parse_url($url);
            
            if (!$parsedUrl || !isset($parsedUrl['host']) || !str_contains($parsedUrl['host'], 'github.com')) {
                return null;
            }

            $pathSegments = explode('/', trim($parsedUrl['path'], '/'));
            
            if (count($pathSegments) < 2) {
                return null;
            }

            $owner = $pathSegments[0];
            $repoName = str_replace('.git', '', $pathSegments[1]);
            
            return [
                'owner' => $owner,
                'name' => $repoName,
                'fullName' => "{$owner}/{$repoName}",
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}