<?php

namespace App\Features\EmailTemplates\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table = 'ip_email_templates';

    protected $primaryKey = 'email_template_id';

    public $timestamps = false;

    protected $fillable = [
        'email_template_title',
        'email_template_type',
        'email_template_body',
        'email_template_subject',
        'email_template_from_name',
        'email_template_from_email',
        'email_template_cc',
        'email_template_bcc',
        'email_template_pdf_template',
    ];
}
