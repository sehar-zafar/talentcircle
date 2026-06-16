<?php

class Badge {
    // Catalog (static). User-specific earned/progress can be added later.
    public static function catalog(): array {
        return [
            [ 'id'=>1,'icon'=>'🚀','label'=>'First Session','rarity'=>'common','cat'=>'Milestones','xp'=>50,'date'=>'Jan 15, 2026','desc'=>'Completed your very first skill swap session on Talent Circle.','progress'=>null ],
            [ 'id'=>2,'icon'=>'🎓','label'=>'First Teacher','rarity'=>'common','cat'=>'Teaching','xp'=>50,'date'=>'Jan 18, 2026','desc'=>'Taught your first session and shared your knowledge.','progress'=>null ],
            [ 'id'=>3,'icon'=>'📚','label'=>'First Learner','rarity'=>'common','cat'=>'Learning','xp'=>30,'date'=>'Jan 20, 2026','desc'=>'Attended your first learning session on Talent Circle.','progress'=>null ],
            [ 'id'=>4,'icon'=>'✅','label'=>'Profile Complete','rarity'=>'common','cat'=>'Milestones','xp'=>20,'date'=>'Jan 14, 2026','desc'=>'Filled out every section of your profile.','progress'=>null ],
            [ 'id'=>5,'icon'=>'👋','label'=>'Welcome Aboard','rarity'=>'common','cat'=>'Milestones','xp'=>10,'date'=>'Jan 14, 2026','desc'=>'Joined the Talent Circle community. Welcome!','progress'=>null ],
            [ 'id'=>6,'icon'=>'💬','label'=>'Conversation Starter','rarity'=>'common','cat'=>'Social','xp'=>15,'date'=>'Jan 19, 2026','desc'=>'Sent your first message to a potential skill partner.','progress'=>null ],
            [ 'id'=>7,'icon'=>'📸','label'=>'Photo Ready','rarity'=>'common','cat'=>'Milestones','xp'=>10,'date'=>'Jan 14, 2026','desc'=>'Uploaded a profile photo to personalise your account.','progress'=>null ],
            [ 'id'=>8,'icon'=>'🔔','label'=>'Notified','rarity'=>'common','cat'=>'Milestones','xp'=>5,'date'=>'Jan 15, 2026','desc'=>'Enabled notifications to stay on top of your sessions.','progress'=>null ],
            [ 'id'=>9,'icon'=>'🗓️','label'=>'Calendar Sync','rarity'=>'common','cat'=>'Milestones','xp'=>15,'date'=>'Jan 16, 2026','desc'=>'Synced your external calendar with Talent Circle.','progress'=>null ],
            [ 'id'=>10,'icon'=>'🎯','label'=>'First Booking','rarity'=>'common','cat'=>'Milestones','xp'=>20,'date'=>'Jan 17, 2026','desc'=>'Booked your first session with another member.','progress'=>null ],

            [ 'id'=>11,'icon'=>'⭐','label'=>'First Review','rarity'=>'common','cat'=>'Social','xp'=>20,'date'=>'Jan 21, 2026','desc'=>'Left your first review for a skill swap partner.','progress'=>null ],
            [ 'id'=>12,'icon'=>'📧','label'=>'Email Verified','rarity'=>'common','cat'=>'Milestones','xp'=>10,'date'=>'Jan 13, 2026','desc'=>'Verified your email address to secure your account.','progress'=>null ],
            [ 'id'=>13,'icon'=>'🌐','label'=>'Skill Listed','rarity'=>'common','cat'=>'Teaching','xp'=>15,'date'=>'Jan 14, 2026','desc'=>'Listed your first teachable skill on your profile.','progress'=>null ],

            [ 'id'=>14,'icon'=>'👍','label'=>'Liked Listing','rarity'=>'common','cat'=>'Social','xp'=>5,'date'=>null,'desc'=>'Liked a skill listing to save it for later.','progress'=>['current'=>0,'total'=>1] ],
            [ 'id'=>15,'icon'=>'🔗','label'=>'Referral Sent','rarity'=>'common','cat'=>'Social','xp'=>25,'date'=>null,'desc'=>'Sent an invite link to a friend to join Talent Circle.','progress'=>['current'=>0,'total'=>1] ],

            [ 'id'=>16,'icon'=>'🔥','label'=>'7-Day Streak','rarity'=>'rare','cat'=>'Streaks','xp'=>75,'date'=>'Feb 3, 2026','desc'=>'Logged in and engaged every day for 7 consecutive days.','progress'=>null ],
            [ 'id'=>17,'icon'=>'⭐','label'=>'5-Star Tutor','rarity'=>'rare','cat'=>'Teaching','xp'=>80,'date'=>'Feb 10, 2026','desc'=>'Received a perfect 5-star rating from a learner.','progress'=>null ],
            [ 'id'=>18,'icon'=>'🤝','label'=>'Social Learner','rarity'=>'rare','cat'=>'Social','xp'=>60,'date'=>'Feb 15, 2026','desc'=>'Successfully swapped skills with 5 different partners.','progress'=>null ],
            [ 'id'=>19,'icon'=>'📖','label'=>'Knowledge Base','rarity'=>'rare','cat'=>'Learning','xp'=>70,'date'=>'Mar 1, 2026','desc'=>'Learned 5 completely different skills across categories.','progress'=>null ],
            [ 'id'=>20,'icon'=>'⚡','label'=>'Speed Learner','rarity'=>'rare','cat'=>'Learning','xp'=>55,'date'=>'Mar 8, 2026','desc'=>'Booked and confirmed a session within 1 hour of posting.','progress'=>null ],
            [ 'id'=>21,'icon'=>'🌍','label'=>'Global Reach','rarity'=>'rare','cat'=>'Social','xp'=>90,'date'=>'Mar 20, 2026','desc'=>'Connected with partners from 3 different countries.','progress'=>null ],
            [ 'id'=>22,'icon'=>'🏃','label'=>'30-Day Streak','rarity'=>'rare','cat'=>'Streaks','xp'=>120,'date'=>'Mar 28, 2026','desc'=>'Kept your activity streak alive for 30 days straight.','progress'=>null ],
            [ 'id'=>23,'icon'=>'💎','label'=>'Token Saver','rarity'=>'rare','cat'=>'Milestones','xp'=>60,'date'=>'Mar 5, 2026','desc'=>'Saved up 100 tokens without spending them for a month.','progress'=>null ],
            [ 'id'=>24,'icon'=>'🎁','label'=>'Gift Giver','rarity'=>'rare','cat'=>'Social','xp'=>65,'date'=>'Feb 28, 2026','desc'=>'Gifted a free session slot to another community member.','progress'=>null ],
            [ 'id'=>25,'icon'=>'🏅','label'=>'Top Rated','rarity'=>'rare','cat'=>'Teaching','xp'=>100,'date'=>null,'desc'=>'Maintain a 4.5+ average rating across 10 sessions.','progress'=>['current'=>7,'total'=>10] ],
            [ 'id'=>26,'icon'=>'📣','label'=>'Influencer','rarity'=>'rare','cat'=>'Social','xp'=>80,'date'=>null,'desc'=>'Get 3 referral signups from your personal invite link.','progress'=>['current'=>1,'total'=>3] ],
            [ 'id'=>27,'icon'=>'🛡️','label'=>'Trusted Member','rarity'=>'rare','cat'=>'Milestones','xp'=>75,'date'=>null,'desc'=>'Complete identity verification to earn the trust badge.','progress'=>['current'=>0,'total'=>1] ],
            [ 'id'=>28,'icon'=>'🎤','label'=>'Public Speaker','rarity'=>'rare','cat'=>'Teaching','xp'=>90,'date'=>null,'desc'=>'Host a group session with 5 or more participants.','progress'=>['current'=>2,'total'=>5] ],
            [ 'id'=>29,'icon'=>'🧩','label'=>'Skill Collector','rarity'=>'rare','cat'=>'Learning','xp'=>85,'date'=>null,'desc'=>'Add 10 different skills to your learning wishlist.','progress'=>['current'=>6,'total'=>10] ],
            [ 'id'=>30,'icon'=>'🌙','label'=>'Night Owl','rarity'=>'rare','cat'=>'Streaks','xp'=>55,'date'=>null,'desc'=>'Complete 5 sessions scheduled after 9 PM local time.','progress'=>['current'=>3,'total'=>5] ],
            [ 'id'=>31,'icon'=>'☀️','label'=>'Early Bird','rarity'=>'rare','cat'=>'Streaks','xp'=>55,'date'=>null,'desc'=>'Complete 5 sessions scheduled before 8 AM local time.','progress'=>['current'=>1,'total'=>5] ],

            [ 'id'=>32,'icon'=>'💜','label'=>'Community Hero','rarity'=>'epic','cat'=>'Social','xp'=>150,'date'=>'Apr 1, 2026','desc'=>'Helped 10+ learners improve a skill, leaving a lasting impact.','progress'=>null ],
            [ 'id'=>33,'icon'=>'🥇','label'=>'Gold Rank','rarity'=>'epic','cat'=>'Milestones','xp'=>200,'date'=>'Apr 12, 2026','desc'=>'Reached the Gold tier — placing you in the top 15% of all users.','progress'=>null ],
            [ 'id'=>34,'icon'=>'🏅','label'=>'Consistent Pro','rarity'=>'epic','cat'=>'Streaks','xp'=>180,'date'=>'Apr 28, 2026','desc'=>'Maintained an active streak for 30 consecutive days.','progress'=>null ],
            [ 'id'=>35,'icon'=>'💡','label'=>'Innovator','rarity'=>'epic','cat'=>'Teaching','xp'=>120,'date'=>'Apr 5, 2026','desc'=>'Taught a skill categorised as emerging or cutting-edge.','progress'=>null ],
            [ 'id'=>36,'icon'=>'🎯','label'=>'Sharpshooter','rarity'=>'epic','cat'=>'Milestones','xp'=>160,'date'=>null,'desc'=>'Complete 100% of booked sessions without cancellations.','progress'=>['current'=>22,'total'=>25] ],
            [ 'id'=>37,'icon'=>'🦋','label'=>'Metamorphosis','rarity'=>'epic','cat'=>'Learning','xp'=>200,'date'=>null,'desc'=>'Complete an entire skill track from beginner to advanced.','progress'=>['current'=>3,'total'=>5] ],
            [ 'id'=>38,'icon'=>'🧠','label'=>'Mastermind','rarity'=>'epic','cat'=>'Teaching','xp'=>175,'date'=>null,'desc'=>'Create a structured curriculum with 8+ lesson modules.','progress'=>['current'=>5,'total'=>8] ],
            [ 'id'=>39,'icon'=>'🌈','label'=>'All-Rounder','rarity'=>'epic','cat'=>'Learning','xp'=>200,'date'=>null,'desc'=>'Complete sessions in 6 different skill categories.','progress'=>['current'=>4,'total'=>6] ],
            [ 'id'=>40,'icon'=>'🔑','label'=>'Key Contributor','rarity'=>'epic','cat'=>'Social','xp'=>160,'date'=>null,'desc'=>'Respond to 20 help requests in the community forum.','progress'=>['current'=>11,'total'=>20] ],
            [ 'id'=>41,'icon'=>'⚔️','label'=>'Skill Duelist','rarity'=>'epic','cat'=>'Social','xp'=>140,'date'=>null,'desc'=>'Win 3 skill challenge competitions hosted on the platform.','progress'=>['current'=>1,'total'=>3] ],
            [ 'id'=>42,'icon'=>'🎪','label'=>'Event Host','rarity'=>'epic','cat'=>'Teaching','xp'=>180,'date'=>null,'desc'=>'Organise and host a community skill event with 10+ attendees.','progress'=>['current'=>0,'total'=>1] ],
            [ 'id'=>43,'icon'=>'📊','label'=>'Data Driven','rarity'=>'epic','cat'=>'Milestones','xp'=>150,'date'=>null,'desc'=>'Track your learning progress daily for 60 days.','progress'=>['current'=>38,'total'=>60] ],

            [ 'id'=>44,'icon'=>'👑','label'=>'Platinum Pioneer','rarity'=>'legendary','cat'=>'Milestones','xp'=>500,'date'=>'May 2, 2026','desc'=>'Reached Platinum rank within your first 90 days — achieved by fewer than 3% of all users.','progress'=>null ],
            [ 'id'=>45,'icon'=>'🌟','label'=>'Rising Star','rarity'=>'legendary','cat'=>'Social','xp'=>300,'date'=>null,'desc'=>'Selected as the featured learner of the month by the editorial team.','progress'=>null ],
            [ 'id'=>46,'icon'=>'🏆','label'=>'Grand Master','rarity'=>'legendary','cat'=>'Teaching','xp'=>500,'date'=>null,'desc'=>'Teach 100 sessions with an average rating of 4.8 or above.','progress'=>['current'=>47,'total'=>100] ],
            [ 'id'=>47,'icon'=>'🌌','label'=>'Universe Builder','rarity'=>'legendary','cat'=>'Milestones','xp'=>1000,'date'=>null,'desc'=>'Reach the maximum Diamond rank — the pinnacle of the reputation system.','progress'=>['current'=>1,'total'=>5] ],
            [ 'id'=>48,'icon'=>'🦄','label'=>'Unicorn Talent','rarity'=>'legendary','cat'=>'Teaching','xp'=>750,'date'=>null,'desc'=>'Have your skill listing featured as "Unique" by the curation team.','progress'=>null ],
            [ 'id'=>49,'icon'=>'🔮','label'=>'Oracle','rarity'=>'legendary','cat'=>'Social','xp'=>600,'date'=>null,'desc'=>'Give advice that leads to 5 learners earning their first badge.','progress'=>['current'=>2,'total'=>5] ],
            [ 'id'=>50,'icon'=>'🧬','label'=>'DNA of TC','rarity'=>'legendary','cat'=>'Milestones','xp'=>800,'date'=>null,'desc'=>'Be among the first 100 members to join Talent Circle at launch.','progress'=>null ],
            [ 'id'=>51,'icon'=>'🛸','label'=>'Trailblazer','rarity'=>'legendary','cat'=>'Milestones','xp'=>500,'date'=>null,'desc'=>'Complete every onboarding challenge without skipping a single step.','progress'=>['current'=>4,'total'=>7] ],
            [ 'id'=>52,'icon'=>'💫','label'=>'Supernova','rarity'=>'legendary','cat'=>'Social','xp'=>900,'date'=>null,'desc'=>'Accumulate 10,000 total XP points across your Talent Circle journey.','progress'=>['current'=>2340,'total'=>10000] ],
        ];
    }
}
?>

