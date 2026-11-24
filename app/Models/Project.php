<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Project extends Model {
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime'];
    public function sources() { return $this->hasMany(Source::class); }
    public function facts() { return $this->hasMany(Fact::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function sections() { return $this->hasMany(Section::class)->orderBy('position'); }
    public function openQuestions() { return $this->questions()->whereNull('answer'); }
}
