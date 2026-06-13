<?php
class GitHubAPI {
    private $token;
    public function __construct($token = null) {
        $this->token = $token ?: PLATFORM_GITHUB_TOKEN;
    }
    private function request($url, $method = 'GET', $data = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: token {$this->token}",
            "Accept: application/vnd.github.v3+json",
            "User-Agent: RDPOrchestrator"
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $httpCode, 'body' => json_decode($response, true)];
    }
    public function createRepoFromTemplate($owner, $repoName, $templateOwner, $templateRepo) {
        $url = "https://api.github.com/repos/{$templateOwner}/{$templateRepo}/generate";
        $data = ['owner' => $owner, 'name' => $repoName, 'private' => true];
        return $this->request($url, 'POST', $data);
    }
    public function triggerWorkflow($owner, $repo, $workflowId, $ref = 'main', $inputs = []) {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflowId}/dispatches";
        $data = ['ref' => $ref, 'inputs' => $inputs];
        return $this->request($url, 'POST', $data);
    }
}