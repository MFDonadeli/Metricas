<?php

/**
* translate_conversions
*
* Coloca nome legíveis nas conversões padrões do Facebook
* @param conversions(array): As conversões possíveis a serem traduzidas
* @param db(object): Objeto do banco de dados
* @return false: Se não houver conversões
*         array: Lista de nomes amigáveis das conversões
*/
function translate_conversions($conversions,$db)
{
  $retorno = false;
  foreach($conversions as $conversion)
  {
    if($conversion->action_type == 'offsite_conversion.fb_pixel_view_content')
      $retorno[$conversion->action_type] = "Visualização de Conteúdo";
    elseif($conversion->action_type == 'offsite_conversion.fb_pixel_initiate_checkout')
      $retorno[$conversion->action_type] = "Inicialização de Compra";
    elseif($conversion->action_type == 'offsite_conversion.fb_pixel_purchase')
      $retorno[$conversion->action_type] = "Compra";
    elseif($conversion->action_type == 'offsite_conversion.fb_pixel_lead')
      $retorno[$conversion->action_type] = "Lead";  
    elseif(strpos($conversion->action_type,'offsite_conversion.custom.') !== false)
    {
      $id = str_replace('offsite_conversion.custom.', '', $conversion->action_type);
      $name = $db->get_custom_conversion_name($id);
      $retorno[$conversion->action_type] = $name;
    }
      
  }

  return $retorno;
}

/**
* processa_contas
*
* Processa os dados da campanha a partir dos dados vindo do Facebook
*   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
*     da tabela
* @param campaigns(array): Os dados vindos do Facebook
* @return array: Dados processados
*/
function processa_contas($conta)
{
  //Atribuição: janela, schedule e pacing do conjunto
  if(array_key_exists('funding_source_details',$conta))
  {
    $fsd = json_encode($conta['funding_source_details']);
    unset($conta['funding_source_details']);
    $conta['funding_source_details'] = $fsd;
  }

  if(array_key_exists('insights',$conta))
  {
    $conta['insights'] = processa_insights($conta['insights'],'conta');
  }

  return $conta;
}

/**
* processa_campaigns
*
* Processa os dados da campanha a partir dos dados vindo do Facebook
*   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
*     da tabela
* @param campaigns(array): Os dados vindos do Facebook
* @return array: Dados processados
*/
function processa_campaigns($campaigns)
{
    if($campaigns == null)
      return null;
    
    for($i=0; $i<count($campaigns); $i++)
      {
        $campaigns[$i]['metrics_imported_at'] = date("Y-m-d H:i:s");
        //Se existir insights a serem processados, faz o processamento
        if(array_key_exists('insights',$campaigns[$i]))
        {
          $campaigns[$i]['insights'] = processa_insights($campaigns[$i]['insights'], 'campaign');
        }
      }

      return $campaigns;
}

/**
* processa_insights
*
* Processa os dados dos insights do tipo a partir dos dados vindo do Facebook
*   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
*     da tabela
* @param insights(array): Os dados vindos do Facebook
* @param tipo(string): Tipo a ser processado: ad, adset, campaign
* @return array: Dados processados
*/
function processa_insights($insights, $tipo)
    {

      $i = 0;
      //Itens cujos dados são obtidos de modo semelhante
      $arr_items = array("video_10_sec_watched_actions", "video_15_sec_watched_actions",
          "video_30_sec_watched_actions", "video_avg_percent_watched_actions",
          "video_avg_time_watched_actions", "video_p100_watched_actions", "canvas_component_avg_pct_view",
          "video_p25_watched_actions", "video_p50_watched_actions", "video_p75_watched_actions",
          "video_p95_watched_actions","website_ctr","website_purchase_roas","cost_per_10_sec_video_view");

      //$arr_action[] = {"actions", "cost_per_action_type", "cost_per_outbound_click"}

      $i = count($insights['data']);
      //Faz processamento dos insights
      foreach($insights['data'] as $insight)
      {
        if(array_key_exists('relevance_score',$insight))
        {
          foreach($insight['relevance_score'] as $key=>$val)
          {
            $insight['relevance_score_'.$key] = $val;
          }
          unset($insight['relevance_score']); 
        } 

        //Processa os ítens em comum
        foreach($arr_items as $item)
        {
          if(array_key_exists($item, $insight))
          {
            foreach($insight[$item][0] as $key=>$val)
            {
              $insight[$item . '_' . $key] = $val;
            }
            unset($insight[$item]);
          } 
        }

        //Processa os actions(conversões, curtidas, comentários, etc) do insight
        //e suas quantidades (value)
        if(array_key_exists('actions',$insight))
        {
          foreach($insight['actions'] as $value)
          {
            $action[$value['action_type']]['action_type'] = $value['action_type'];
            $action[$value['action_type']]['value'] = $value['value'];
            //Associa o tipo do action com o id do insight (cada tipo: ad, adset, campanha
            //  possui um insight e cada insight possui vários actions)
            $action[$value['action_type']][$tipo.'_id'] = $insight[$tipo.'_id'];
            $action[$value['action_type']]['account_id'] = $insight['account_id'];
          }
          unset($insight['actions']);
        }

        //Processa o valor atribuído aos actions (O valor vem quando está configurado o valor
        // no pixel)
        if(array_key_exists('action_values',$insight))
        {
            foreach($insight['action_values'] as $value)
            {
                $action[$value['action_type']]['action_type'] = $value['action_type'];
                $action[$value['action_type']]['action_values'] = $value['value'];
                //Associa o id do action com o insight
                $action[$value['action_type']][$tipo.'_id'] = $insight[$tipo.'_id'];
                $action[$value['action_type']]['account_id'] = $insight['account_id'];
            }
            unset($insight['action_values']);       
        }

        //Outobund_clicks: Número de cliques que levam para fora do Facebook
        if(array_key_exists('outbound_clicks',$insight))
        {
          $value['action_type'] = $insight['outbound_clicks'][0]['action_type'];
          $action[$value['action_type']]['action_type'] = $value['action_type'];
          $action[$value['action_type']]['value'] = $value['value'];
          $action[$value['action_type']][$tipo.'_id'] = $insight[$tipo.'_id'];
          $action[$value['action_type']]['account_id'] = $insight['account_id'];
          unset($insight['outbound_clicks']);
        }

        //Custo por ação
        if(array_key_exists('cost_per_action_type',$insight))
        {
          foreach($insight['cost_per_action_type'] as $value)
          {
            $action[$value['action_type']]['action_type'] = $value['action_type'];
            $action[$value['action_type']]['cost'] = $value['value'];
          }
          unset($insight['cost_per_action_type']);
        }

        //Custo por clique que levaram para fora do Facebook
        if(array_key_exists('cost_per_outbound_click',$insight))
        {
          $value['action_type'] = $insight['cost_per_outbound_click'][0]['action_type'];
          $action[$value['action_type']]['action_type'] = $value['action_type'];
          $action[$value['action_type']]['cost'] = $value['value'];
          unset($insight['cost_per_outbound_click']);
        }

        //Número de pessoas que fizeram aquela ação
        if(array_key_exists('unique_actions',$insight))
        {
          foreach($insight['unique_actions'] as $value)
          {
            $action[$value['action_type']]['action_type'] = $value['action_type'];
            $action[$value['action_type']]['unique'] = $value['value'];
          }
          unset($insight['unique_actions']);
        }

        //Número de pessoas que foram levadas para fora do Facebook
        if(array_key_exists('unique_outbound_clicks',$insight))
        {
          $value['action_type'] = $insight['unique_outbound_clicks'][0]['action_type'];
          $action[$value['action_type']]['action_type'] = $value['action_type'];
          $action[$value['action_type']]['unique'] = $value['value'];
          unset($insight['unique_outbound_clicks']);
        }

        //Custo por pessoas
        if(array_key_exists('cost_per_unique_action_type',$insight))
        {
          foreach($insight['cost_per_unique_action_type'] as $value)
          {
            $action[$value['action_type']]['action_type'] = $value['action_type'];
            $action[$value['action_type']]['unique_cost'] = $value['value'];
          }
          unset($insight['cost_per_unique_action_type']);
        }

        //Custo por pessoas que foram para fora do Facebook
        if(array_key_exists('cost_per_unique_outbound_click',$insight))
        {
          $value['action_type'] = $insight['cost_per_unique_outbound_click'][0]['action_type'];
          $action[$value['action_type']]['action_type'] = $value['action_type'];
          $action[$value['action_type']]['unique_cost'] = $value['value'];
          unset($insight['cost_per_unique_outbound_click']);
        }

        //%De cliques que viram o tipo e foram para fora do Facebook
        if(array_key_exists('outbound_clicks_ctr', $insight))
        {
          $insight['outbound_clicks_ctr_value'] = $insight['outbound_clicks_ctr'][0]['value'];  
          $insight['outbound_clicks_ctr_action_type'] = $insight['outbound_clicks_ctr'][0]['action_type'];  
          unset($insight['outbound_clicks_ctr']);
        }

        //%De pessoas que viram o tipo e foram para fora do Facebook
        if(array_key_exists('unique_outbound_clicks_ctr', $insight))
        {
          $insight['unique_outbound_clicks_ctr_value'] = $insight['unique_outbound_clicks_ctr'][0]['value']; 
          $insight['unique_outbound_clicks_ctr_action_type'] = $insight['unique_outbound_clicks_ctr'][0]['action_type']; 
          unset($insight['unique_outbound_clicks_ctr']);
        }

        //RETORNO
        $insights_ret = $insight; 
        if(isset($action))  
        {
          $insights_ret['action'] = $action;
          unset($action);
        }
      }

      log_message('debug','****IMPORTANTE****: Quantidade de insights:' . $i);
      return $insights_ret;
      
    }

    /**
    * processa_ads
    *
    * Processa os dados do anúncio a partir dos dados vindo do Facebook
    *   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
    *     da tabela
    * @param ads(array): Os dados vindos do Facebook
    * @return array: Dados processados
    */
    function processa_ads($ads)
    {
      if($ads == null)
        return null;

      foreach($ads as $ad)
      {
        unset($ad['campaign']);
        //Quando o anúncio não foi aprovado para um plataforma ou desaprovado total
        if(array_key_exists('ad_review_feedback',$ad))
        {
          if(array_key_exists('placement_specific',$ad['ad_review_feedback']))
          {
            $v = key($ad['ad_review_feedback']['global']);
            $ad['ad_review_feedback_reason'] = $v;
            $ad['ad_review_feedback_description'] = $ad['ad_review_feedback']['global'][$v];

            $encode  = json_encode($ad['ad_review_feedback']['placement_specific']);
            unset($ad['ad_review_feedback']['placement_specific']);
            $ad['ad_review_feedback']['placement_specific'] = $encode;
          }
          unset($ad['ad_review_feedback']);
        }

        //Recomendações para melhorar o anúncio
        if(array_key_exists('recommendations',$ad))
        {
          $encode  = json_encode($ad['recommendations']);
          unset($ad['recommendations']); 
          $ad['recommendations'] = $encode;
        }

        if(array_key_exists('tracking_specs',$ad))
        {
          /*$ad['tracking_specs_action_type'] = $ad['tracking_specs'][0]['action.type'][0];
          foreach($ad['tracking_specs'] as $spec)
          {
            unset($spec['action.type']);
            $key = key($spec);
            if($key != '')
              $ad['tracking_specs_'.$key] = $spec[$key][0];
          }*/
          $encode  = json_encode($ad['tracking_specs']);
          unset($ad['tracking_specs']); 
          $ad['tracking_specs'] = $encode;
        }

        if(array_key_exists('bid_info', $ad))
        {
          $ad['bid_info'] = $ad['bid_info']['ACTIONS'];
        }

        if(array_key_exists('insights',$ad))
        {
          $ad['insights'] = processa_insights($ad['insights'],'ad');
        }

        //Dados do criativo
        if(array_key_exists('creative', $ad))
        {
          unset($ad['creative']['image_crops']);
          unset($ad['creative']['platform_customizations']);

          $ad['creative']['ad_id'] = $ad['id'];
          if(array_key_exists('object_story_spec',$ad['creative']))
          {
            $encode = json_encode($ad['creative']['object_story_spec']);
            unset($ad['creative']['object_story_spec']); 
            $ad['creative']['object_story_spec'] = $encode;
          } 

          if(array_key_exists('template_url_spec',$ad['creative']))
          {
            $encode = json_encode($ad['creative']['template_url_spec']);
            unset($ad['creative']['template_url_spec']); 
            $ad['creative']['template_url_spec'] = $encode;
          } 
          //unset($ad['creative']);
        }

        $ads_ret[] = $ad;

      }
      return $ads_ret;
    }

    /**
    * processa_adsets
    *
    * Processa os dados do conjunto a partir dos dados vindo do Facebook
    *   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
    *     da tabela
    * @param adsets(array): Os dados vindos do Facebook
    * @return array: Dados processados
    */
    function processa_adsets($adsets)
    {
      if($adsets == null)
        return null;

      foreach($adsets as $adset)
      {
        //Atribuição: janela, schedule e pacing do conjunto
        if(array_key_exists('attribution_spec',$adset))
        {
          $adset['attribution_spec_event_type'] = $adset['attribution_spec'][0]['event_type'];
          $adset['attribution_spec_window_days'] = $adset['attribution_spec'][0]['window_days'];
          unset($adset['attribution_spec']);
        }

        if(array_key_exists('adset_schedule', $adset))
        {
          $adset['adset_schedule'] = json_encode($adset['adset_schedule']);
        }
        
        if(array_key_exists('pacing_type',$adset))
          $adset['pacing_type'] = $adset['pacing_type'][0];

        if(array_key_exists('promoted_object',$adset))
        {
          foreach($adset['promoted_object'] as $key=>$val)
          {
            $adset['promoted_object_'.$key] = $val;
          }
          unset($adset['promoted_object']); 
        }

        if(array_key_exists('frequency_control_specs', $adset))
        {
          foreach($adset['frequency_control_specs'][0] as $key=>$val)
          {
            $adset['frequency_control_specs_'.$key] = $val;
          }
          log_message('debug','****IMPORTANTE****: Quantidade de frequency_control_specs:' . count($adset['frequency_control_specs']));
          unset($adset['frequency_control_specs']);   
        }

        if(array_key_exists('bid_info', $adset))
        {
          $adset['bid_info'] = $adset['bid_info']['ACTIONS'];
        }

        if(array_key_exists('insights',$adset))
        {
          $adset['insights'] = processa_insights($adset['insights'],'adset');
        }

        if(array_key_exists('targeting',$adset))
        {
          $adset['targeting'] = processa_targeting($adset['targeting'],$adset['id']);
        }
        
        $adsets_ret[] = $adset;
      }

      return $adsets_ret;
    }

    /**
    * processa_targeting
    *
    * Processa os dados do targeting do Adset a partir dos dados vindo do Facebook
    *   - A intenção é colocar em um array associativo cujos index será igual o nome do campo
    *     da tabela
    * @param targeting(array): Os dados vindos do Facebook
    * @param adset_id(string): Id do adset cujo targeting vai ser processado
    * @return array: Dados processados
    */
    function processa_targeting($targeting,$adset_id)
    {
      $retorno['adset_id'] = $adset_id;
      foreach($targeting as $key => $val)
      {
        if($key == 'flexible_spec')
        {
          for($i=0;$i<count($val);$i++)
          {
            $retorno['flexible_spec_' . key($val[$i])] = json_encode($val[$i][key($val[$i])]);
          }
          unset($retorno['flexible_spec']);
        }
        $retorno[$key] = json_encode($val);
      }

      return $retorno;
    }

    /**
     * createDateRange
     * 
     * Returns every date between two dates as an array
     * @param string $startDate the start of the date range
     * @param string $endDate the end of the date range
     * @param string $format DateTime format, default is Y-m-d
     * @return array returns every date between $startDate and $endDate, formatted as "Y-m-d"
     */
    function createDateRange($startDate, $format = "Y-m-d")
    {
        $today = date($format);

        $begin = new DateTime($startDate);
        $end = new DateTime($today);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($begin, $interval, $end);

        $range = [];
        foreach ($dateRange as $date) {
            $range[$date->format($format)] = 0;
        }

        return $range;
    }

    
  ?>  