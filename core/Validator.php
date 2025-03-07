<?php
namespace App\Core;

class Validator {
    private $data;
    private $errors = [];
    
    /**
     * Create a new validator instance
     *
     * @param array $data Data to validate
     */
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Check if fields are required
     *
     * @param array $fields Fields to check
     * @return $this
     */
    public function required($fields) {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty($this->data[$field])) {
                $this->errors[$field] = "Het veld {$field} is verplicht";
            }
        }
        
        return $this;
    }
    
    /**
     * Check if fields are numeric
     *
     * @param string|array $fields Field(s) to check
     * @return $this
     */
    public function numeric($fields) {
        $fields = is_array($fields) ? $fields : [$fields];
        
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
                $this->errors[$field] = "Het veld {$field} moet een getal zijn";
            }
        }
        
        return $this;
    }
    
    /**
     * Check if a field is a valid date
     *
     * @param string|array $fields Field(s) to check
     * @return $this
     */
    public function date($fields) {
        $fields = is_array($fields) ? $fields : [$fields];
        
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && !strtotime($this->data[$field])) {
                $this->errors[$field] = "Het veld {$field} moet een geldige datum zijn";
            }
        }
        
        return $this;
    }
    
    /**
     * Check if a field is in a list of valid values
     *
     * @param string $field Field to check
     * @param array $values Valid values
     * @return $this
     */
    public function in($field, $values) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = "Het veld {$field} moet één van de volgende waarden zijn: " . implode(', ', $values);
        }
        
        return $this;
    }
    
    /**
     * Check if a field is a valid email address
     *
     * @param string|array $fields Field(s) to check
     * @return $this
     */
    public function email($fields) {
        $fields = is_array($fields) ? $fields : [$fields];
        
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "Het veld {$field} moet een geldig e-mailadres zijn";
            }
        }
        
        return $this;
    }
    
    /**
     * Check if a field has a minimum length
     *
     * @param string $field Field to check
     * @param int $length Minimum length
     * @return $this
     */
    public function min($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "Het veld {$field} moet minimaal {$length} tekens bevatten";
        }
        
        return $this;
    }
    
    /**
     * Check if a field matches another field
     *
     * @param string $field Field to check
     * @param string $otherField Field to compare with
     * @param string $fieldName Name of the field (for error message)
     * @param string $otherFieldName Name of the other field (for error message)
     * @return $this
     */
    public function same($field, $otherField, $fieldName = null, $otherFieldName = null) {
        if (isset($this->data[$field]) && isset($this->data[$otherField]) &&
            $this->data[$field] !== $this->data[$otherField]) {
            $fieldName = $fieldName ?: $field;
            $otherFieldName = $otherFieldName ?: $otherField;
            $this->errors[$field] = "Het veld {$fieldName} moet overeenkomen met {$otherFieldName}";
        }
        
        return $this;
    }
    
    /**
     * Check if validation passes
     *
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation fails
     *
     * @return bool
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors() {
        return $this->errors;
    }
}