<?php
/**
 * Dit bestand definieert class aliases om namespace problemen op te lossen
 * 
 * Dit script moet worden ingeladen NADAT alle classes geladen zijn
 */

// Functie om alle klassen te verzamelen en aliases aan te maken
function create_all_class_aliases() {
    // Verzamel alle geladen klassen
    $loadedClasses = get_declared_classes();
    $coreNamespacePrefix = 'App\\Core\\';
    $controllerNamespacePrefix = 'App\\Controllers\\';
    $modelNamespacePrefix = 'App\\Models\\';
    
    $aliasesCreated = 0;
    
    foreach ($loadedClasses as $className) {
        // Check voor core klassen met namespace en maak ook een niet-namespace versie
        if (strpos($className, $coreNamespacePrefix) === 0) {
            $shortName = substr($className, strlen($coreNamespacePrefix));
            
            // Als de niet-namespace versie nog niet bestaat, definieer deze
            if (!class_exists($shortName, false)) {
                class_alias($className, $shortName);
                $aliasesCreated++;
            }
        }
        // Check voor controller klassen met namespace
        else if (strpos($className, $controllerNamespacePrefix) === 0) {
            $shortName = substr($className, strlen($controllerNamespacePrefix));
            
            // Als de niet-namespace versie nog niet bestaat, definieer deze
            if (!class_exists($shortName, false)) {
                class_alias($className, $shortName);
                $aliasesCreated++;
            }
        }
        // Check voor model klassen met namespace
        else if (strpos($className, $modelNamespacePrefix) === 0) {
            $shortName = substr($className, strlen($modelNamespacePrefix));
            
            // Als de niet-namespace versie nog niet bestaat, definieer deze
            if (!class_exists($shortName, false)) {
                class_alias($className, $shortName);
                $aliasesCreated++;
            }
        }
        // Check ook niet-namespace klassen en maak namespace versies
        else if (in_array($className, ['Router', 'Session', 'Database', 'Auth', 'Controller'])) {
            if (!class_exists($coreNamespacePrefix . $className, false)) {
                class_alias($className, $coreNamespacePrefix . $className);
                $aliasesCreated++;
            }
        }
        // Check controllers zonder namespace
        else if (strpos($className, 'Controller') !== false && strpos($className, '\\') === false) {
            if (!class_exists($controllerNamespacePrefix . $className, false)) {
                class_alias($className, $controllerNamespacePrefix . $className);
                $aliasesCreated++;
            }
        }
    }
    
    return $aliasesCreated;
}

// Voer de alias creator function uit en log het resultaat
$count = create_all_class_aliases();
error_log("Class aliases aangemaakt voor $count klassen");

// Specifieke aliassen voor kritieke klassen
if (class_exists('Router') && !class_exists('App\\Core\\Router')) {
    class_alias('Router', 'App\\Core\\Router');
    error_log("Geforceerde alias gemaakt voor Router naar App\\Core\\Router");
}
if (class_exists('Session') && !class_exists('App\\Core\\Session')) {
    class_alias('Session', 'App\\Core\\Session');
    error_log("Geforceerde alias gemaakt voor Session naar App\\Core\\Session");
} 