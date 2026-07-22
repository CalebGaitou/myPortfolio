<?php
    // Shared between manageModules.php and uploadFolder.php.

    function slugifyCategoryName(string $name): string {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $slug = '';
        foreach ($words as $i => $word) {
            $slug .= $i === 0 ? strtolower($word) : ucfirst(strtolower($word));
        }
        return $slug;
    }
?>
