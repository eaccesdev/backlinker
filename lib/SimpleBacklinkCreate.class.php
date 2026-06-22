<?php

class SimpleBacklinkCreate
{
    private $target = '';
    private $targetValid = false;
    private $targetError = '';
    private $urls = [];
    private $results = [];
    private $anchorText = null;
    private $strategy = 'html_snippet';

    private $rel = 'nofollow';
    private $targetBlank = false;

    // New: Multiple anchor variations for natural look
    private $anchorVariations = [];

    public function setTarget($target)
    {
        $target = trim((string)$target);
        if ($target !== '' && filter_var($target, FILTER_VALIDATE_URL)) {
            $this->target = $target;
            $this->targetValid = true;
            $this->targetError = '';
        } else {
            $this->target = $target;
            $this->targetValid = false;
            $this->targetError = 'Target URL is invalid.';
        }
    }

    public function getTarget() { return $this->target; }
    public function getTargetError() { return $this->targetError; }

    public function setUrls($urls)
    {
        if (!is_array($urls)) $urls = [$urls];
        $urls = array_map('trim', $urls);
        $urls = array_filter($urls);
        $this->urls = array_values(array_filter($urls, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));
    }

    public function setAnchorText($anchorText)
    {
        $this->anchorText = $anchorText ? trim($anchorText) : null;
    }

    public function setStrategy($strategy)
    {
        $this->strategy = in_array($strategy, ['html_snippet', 'page_template']) ? $strategy : 'html_snippet';
    }

    public function setRel($rel)
    {
        $allowed = ['nofollow', 'dofollow', 'sponsored', 'ugc', ''];
        $this->rel = in_array($rel, $allowed) ? $rel : 'nofollow';
    }

    public function setTargetBlank($targetBlank)
    {
        $this->targetBlank = (bool)$targetBlank;
    }

    public function process()
    {
        $this->results = [];
        if (!$this->targetValid) return;

        $this->prepareAnchorVariations();

        foreach ($this->urls as $sourceUrl) {
            $content = $this->generateVariedBacklink($sourceUrl);
            $this->results[$sourceUrl] = ['content' => $content];
        }
    }

    public function getResults() { return $this->results; }

    private function prepareAnchorVariations()
    {
        $base = $this->anchorText ?: parse_url($this->target, PHP_URL_HOST) ?: 'website';
        $base = str_replace('www.', '', strtolower($base));

        $this->anchorVariations = [
            $base,
            "Visit " . $base,
            "Learn more at " . $base,
            $base . " official site",
            "Best " . $base,
            "Check out " . $base,
            "Read more on " . $base,
            ucfirst($base) . " resources",
            "Great guide from " . $base,
            $base . " blog"
        ];
    }

    private function generateVariedBacklink($sourceUrl)
    {
        $href = htmlspecialchars($this->target, ENT_QUOTES, 'UTF-8');
        $anchor = htmlspecialchars($this->anchorVariations[array_rand($this->anchorVariations)], ENT_QUOTES, 'UTF-8');

        $relAttr = '';
        if ($this->rel === 'dofollow') {
            // do nothing
        } elseif ($this->rel && $this->rel !== 'dofollow') {
            $relAttr = ' rel="' . htmlspecialchars($this->rel, ENT_QUOTES) . '"';
        }

        $targetAttr = $this->targetBlank ? ' target="_blank" rel="noopener noreferrer"' : '';

        $snippet = '<a href="' . $href . '"' . $relAttr . $targetAttr . '>' . $anchor . '</a>';

        // Add variety with surrounding text for full pages
        if ($this->strategy === 'html_snippet') {
            return $snippet;
        }

        // Full page template with more natural context
        $contexts = [
            "<p>I recently discovered a great resource at {$snippet}.</p>",
            "<p>Highly recommend checking {$snippet} for more details.</p>",
            "<p>Useful guide: {$snippet}</p>",
            "<p>For the best options, see {$snippet}.</p>"
        ];

        $context = $contexts[array_rand($contexts)];

        return '<!doctype html><html><head><meta charset="utf-8"><title>Resource</title></head><body>' .
               '<h1>Useful Links</h1>' . $context .
               '<!-- Source: ' . htmlspecialchars($sourceUrl) . ' -->' .
               '</body></html>';
    }
}
?>
