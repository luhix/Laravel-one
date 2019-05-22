<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        static::creating(function ($user){
            $user->activation_token = str_random(30);
        });
    }

    /**
     *
     * @DESC: 指明一个用户拥有多条微博
     *
     * @author: HX
     * @Time: 2019/5/22   9:13
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    /**
     *
     * @DESC: 微博消息动态流
     *
     * @author: HX
     * @Time: 2019/5/22   15:40
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feed()
    {
        //自己的动态
        /*return $this->statuses()
            ->orderBy('created_at', 'desc');*/

        /*
         * 所有关注用户的动态和自己的动态
         */
        $user_ids = $this->followings->pluck('id')->toArray();
        array_push($user_ids, $this->id);
        return Status::whereIn('user_id', $user_ids)
            ->with('user')
            ->orderBy('created_at', 'desc');
    }

    /**
     *
     * @DESC: 获取粉丝关系列表
     *
     * @author: HX
     * @Time: 2019/5/22   14:03
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    /**
     *
     * @DESC: 获取粉丝用户关注人列表
     *
     * @author: HX
     * @Time: 2019/5/22   14:04
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followings()
    {
        return $this->belongsToMany(User::class,'followers','follower_id','user_id');
    }
    
    public function follow($user_ids)
    {
        if ( ! is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        
        $this->followings()->sync($user_ids, false);
    }
    
    public function unfollow($user_ids)
    {
        if ( !is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        
        $this->followings()->detach($user_ids);
    }

    /**
     *
     * @DESC: 判断是否被关注
     *
     * @author: HX
     * @Time: 2019/5/22   15:38
     *
     * @param $user_id
     * @return mixed
     */
    public function isFollowing($user_id)
    {
        return $this->followings->contains($user_id);
    }
}
