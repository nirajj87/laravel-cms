<?php

namespace App\Enums;

enum FieldType: string
{
    case Text = 'text';
    case LongText = 'long_text';
    case RichText = 'rich_text';
    case Number = 'number';
    case Currency = 'currency';
    case Price = 'price';
    case Discount = 'discount';
    case Image = 'image';
    case Gallery = 'gallery';
    case File = 'file';
    case Url = 'url';
    case Email = 'email';
    case Phone = 'phone';
    case Date = 'date';
    case DateTime = 'datetime';
    case Boolean = 'boolean';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Rating = 'rating';
    case Color = 'color';
    case Icon = 'icon';
    case VideoUrl = 'video_url';
    case ExternalLink = 'external_link';
    case Tags = 'tags';
    case Button = 'button';
    case Category = 'category';
    case DetailPage = 'detail_page';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::LongText => 'Long text',
            self::RichText => 'Rich text',
            self::Number => 'Number',
            self::Currency => 'Currency',
            self::Price => 'Price',
            self::Discount => 'Discount',
            self::Image => 'Image',
            self::Gallery => 'Gallery',
            self::File => 'File',
            self::Url => 'URL',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Date => 'Date',
            self::DateTime => 'Date and time',
            self::Boolean => 'Boolean',
            self::Select => 'Select',
            self::MultiSelect => 'Multi select',
            self::Radio => 'Radio',
            self::Checkbox => 'Checkbox',
            self::Rating => 'Rating',
            self::Color => 'Color',
            self::Icon => 'Icon',
            self::VideoUrl => 'Video URL',
            self::ExternalLink => 'External link',
            self::Tags => 'Tags',
            self::Button => 'Button',
            self::Category => 'Category',
            self::DetailPage => 'Detail page',
        };
    }

    public function storesValue(): bool
    {
        return ! in_array($this, [self::Category, self::DetailPage], true);
    }

    public function needsOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect, self::Radio], true);
    }

    public function isSearchable(): bool
    {
        return in_array($this, [self::Text, self::LongText, self::RichText, self::Tags, self::Select], true);
    }
}
