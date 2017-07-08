<?php

define ('ACCOUNTS', 'name,
account_status,
amount_spent,
created_time,age');

define('CAMPAIGNS', 'campaigns,
status,
id,
name,
account_id,
budget_rebalance_flag,
buying_type,
can_use_spend_cap,
configured_status,
created_time,
effective_status,
objective,
spend_cap,
start_time,
stop_time,
updated_time');

define('ADSETS', 'id,
status,
campaign_id,
name,
account_id,
adset_schedule,
attribution_spec,
bid_amount,
bid_info,
billing_event,
budget_remaining,
configured_status,
created_time,
daily_budget,
effective_status,
end_time,
frequency_cap_reset_period,
is_autobid,
is_average_price_pacing,
lifetime_budget,
lifetime_imps,
optimization_goal,
pacing_type,
promoted_object,
recurring_budget_semantics,
rtb_flag,
start_time,
updated_time,
use_new_app_click,
targeting');

define('ADS', 'id,
status,
adset_id,
campaign_id,
account_id,
name,
ad_review_feedback,
bid_amount,
bid_info,
bid_type,
campaign,
configured_status,
created_time,
effective_status,
last_updated_by_app_id,
recommendations,
tracking_specs,
updated_time');

define ('INSIGHTS', 'actions,
cost_per_inline_link_click,
account_id,
account_name,
adset_id,
ad_id,
campaign_id,
action_values,
app_store_clicks,
call_to_action_clicks,
canvas_avg_view_percent,
canvas_avg_view_time,
clicks,
cost_per_10_sec_video_view,
cost_per_estimated_ad_recallers,
cost_per_inline_post_engagement,
cost_per_unique_inline_link_click,
cost_per_unique_click,
cost_per_action_type,
cost_per_outbound_click,
cost_per_total_action,
cpc,
cpm,
cpp,
ctr,
date_start,
date_stop,
deeplink_clicks,
estimated_ad_recall_rate,
estimated_ad_recallers,
frequency,
impressions,
inline_link_click_ctr,
inline_link_clicks,
inline_post_engagement,
objective,
reach,
social_reach,
social_spend,
spend,
total_action_value,
unique_clicks,
unique_ctr,
unique_inline_link_click_ctr,
unique_inline_link_clicks,
website_clicks,
age_targeting,
buying_type,
canvas_component_avg_pct_view,
cost_per_unique_action_type,
cost_per_unique_outbound_click,
created_time,
gender_targeting,
labels,
location,
mobile_app_purchase_roas,
outbound_clicks,
outbound_clicks_ctr,
place_page_name,
relevance_score,
social_clicks,
social_impressions,
total_actions,
total_unique_actions,
unique_actions,
unique_link_clicks_ctr,
unique_outbound_clicks,
unique_outbound_clicks_ctr,
unique_social_clicks,
updated_time,
video_10_sec_watched_actions,
video_15_sec_watched_actions,
video_30_sec_watched_actions,
video_avg_percent_watched_actions,
video_avg_time_watched_actions,
video_p100_watched_actions,
video_p25_watched_actions,
video_p50_watched_actions,
video_p75_watched_actions,
video_p95_watched_actions,
website_ctr,
website_purchase_roas');

define('CREATIVES', 'account_id,
applink_treatment,
body,
call_to_action_type,
effective_instagram_story_id,
effective_object_story_id,
id,
image_crops,
image_hash,
image_url,
instagram_actor_id,
instagram_permalink_url,
instagram_story_id,
link_url,
name,
object_id,
object_story_id,
object_story_spec,
object_type,
object_url,
platform_customizations,
product_set_id,
status,
template_url,
template_url_spec,
title,
url_tags,
use_page_actor_override,
video_id');

function get_param_contas()
{
    $accounts = str_replace("\n","",ACCOUNTS);
    $campaigns = str_replace("\n","",CAMPAIGNS);
    $insights = str_replace("\n","",INSIGHTS);
    $adsets = str_replace("\n","",ADSETS);
    $ads = str_replace("\n","",ADS);
    $creative = str_replace("\n","",CREATIVES);

return '?fields=' . $accounts . ',campaigns{' . $campaigns . ',insights{' . $insights . '}},adsets{' . 
 $adsets . ',insights{' . $insights . '}},ads{' . $ads . ',insights{' . $insights . '},creative{' . $creative .
 '}},insights{' .
 $insights . '}';  
} 

function get_param_contas_data($dt_inicio)
{
    $insights = str_replace("\n","",INSIGHTS);

    $dt = date('Y-m-d', time());
 
$aaa = "?fields=" . $insights . "&time_increment=1&time_range={'since':'" . $dt_inicio .
 "','until':'" . $dt . "'}";

 return $aaa;

}

?>