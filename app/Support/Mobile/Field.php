<?php

namespace App\Support\Mobile;

// Form field descriptions for staff actions (see StaffActions). The mobile app
// renders each action's form from these, so field names must match the web
// controller's validation exactly. `source` names a list in GET /staff/lookups.
final class Field
{
    public static function textarea(string $name, string $label, bool $required = false, ?int $max = null, ?string $hint = null): array
    {
        return array_filter(['name' => $name, 'label' => $label, 'type' => 'textarea', 'required' => $required, 'max' => $max, 'hint' => $hint], fn ($v) => $v !== null);
    }

    public static function select(string $name, string $label, array $options, bool $required = false, ?string $default = null, ?string $hint = null): array
    {
        return array_filter([
            'name' => $name,
            'label' => $label,
            'type' => 'select',
            'required' => $required,
            'options' => array_map(fn ($o) => is_array($o) ? $o : ['value' => $o, 'label' => $o], $options),
            'default' => $default,
            'hint' => $hint,
        ], fn ($v) => $v !== null);
    }

    public static function radio(string $name, string $label, array $options, bool $required = true, ?string $default = null): array
    {
        return ['type' => 'radio'] + self::select($name, $label, $options, $required, $default);
    }

    // A list from GET /staff/lookups: sla_rules, self_triage_rules, assignees, workload_classes.
    public static function lookup(string $name, string $label, string $source, bool $required = false, ?string $hint = null): array
    {
        return array_filter(['name' => $name, 'label' => $label, 'type' => 'lookup', 'source' => $source, 'required' => $required, 'hint' => $hint], fn ($v) => $v !== null);
    }

    public static function number(string $name, string $label, ?string $hint = null): array
    {
        return array_filter(['name' => $name, 'label' => $label, 'type' => 'number', 'required' => false, 'hint' => $hint], fn ($v) => $v !== null);
    }

    public static function boolean(string $name, string $label, ?string $hint = null): array
    {
        return array_filter(['name' => $name, 'label' => $label, 'type' => 'boolean', 'required' => false, 'hint' => $hint], fn ($v) => $v !== null);
    }

    public static function files(string $name = 'attachments', string $label = 'Attachments'): array
    {
        return ['name' => $name, 'label' => $label, 'type' => 'files', 'required' => false, 'max' => 5];
    }
}
