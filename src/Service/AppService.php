<?php

namespace App\Service;

use Manao\Bitrix\Rest\Client\Client;
use Manao\Bitrix\Rest\Client\RestMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class AppService
{
    private $select;
    private $order;
    private $filter;
    private $params;
    private $client;
    private $fields;
    private $dealList;
    private $formatDealList;
    private $errors;
    private $encoder;
    private const countElement = 50;
    private const countQueryInBatch = 50;

    function __construct(Client $client, $categoryDealID){

        $this->select = [
            'DATE_CREATE',
            'OPPORTUNITY',
            'UF_CRM_1642597942',
            'UF_CRM_1642597927',
        ];

        $this->order = [
            'ID' => 'DESC'
        ];

        $this->filter = [
            'CATEGORY_ID' => $categoryDealID
        ];

        $this->params = [
            'select'    => $this->select,
            'order'     => $this->order,
            'filter'    => $this->filter,
            'start'     => 0
        ];

        $this->dealList = [];
        $this->client = $client;

        $this->fields = [
            'COUNT_INIT_REGULAR'    => 0,
            'COUNT_REPEAT_REGULAR'  => 0,
            'COUNT_REGULAR'         => 0,
            'COUNT_SINGLE'          => 0,
            'COUNT_ATTEMPT'         => 0,
            'SUM_REGULAR'           => 0,
            'SUM_SINGLE'            => 0,
            'SUM_COMMON'            => 0,
            'AVG_REGULAR'           => 0
        ];

        $this->dealList = [];
        $this->formatDealList = [];
        $this->errors = [];
        $this->encoder = new JsonEncoder();

    }
    
    public function getData(Request $request): Array
    {        
        $this->getDealListAll($request);
        $this->formatingDealList();     
        $data = $this->processingDealList();
        
        return ['data' => $data, 'errors' => $this->errors];
    }

    public function processingDealList(){
        $mainStat = ['LABEL' => 'Итого'] + $this->fields;

        foreach($this->formatDealList as $key => $dealsDate){
            $dayStat = ['LABEL' => $key] + $this->fields;

            foreach($dealsDate as $deal){
                $this->processing($deal, $dayStat, $mainStat);
            }

            $dayStat['AVG_REGULAR'] = 0;
            if($dayStat['COUNT_REGULAR'] > 0){
                $dayStat['AVG_REGULAR'] = round($dayStat['SUM_REGULAR'] / $dayStat['COUNT_REGULAR'], 2);
            }
            $result[] = $dayStat;
        }

        $mainStat['AVG_REGULAR'] = 0;
        if($mainStat['COUNT_REGULAR'] > 0){
            $mainStat['AVG_REGULAR'] = round($mainStat['SUM_REGULAR'] / $mainStat['COUNT_REGULAR'], 2);
        }

        $result['main'] = $mainStat;

        return $result;
    }

    public function processing($deal, &$dayStat, &$mainStat){

        if($this->isInit($deal) && $this->isSuccess($deal)){
            $dayStat['COUNT_INIT_REGULAR'] += 1;
            $mainStat['COUNT_INIT_REGULAR'] += 1;
        }

        if($this->isRepeat($deal) && $this->isSuccess($deal)){
            $dayStat['COUNT_REPEAT_REGULAR'] += 1;
            $mainStat['COUNT_REPEAT_REGULAR'] += 1;
        }

        if($this->isRegular($deal) && $this->isSuccess($deal)){
            $dayStat['COUNT_REGULAR'] += 1;
            $dayStat['SUM_REGULAR'] += $deal['OPPORTUNITY'];
            $dayStat['SUM_COMMON'] += $deal['OPPORTUNITY'];

            $mainStat['COUNT_REGULAR'] += 1;
            $mainStat['SUM_REGULAR'] += $deal['OPPORTUNITY'];
            $mainStat['SUM_COMMON'] += $deal['OPPORTUNITY'];
        }

        if($this->isSingle($deal) && $this->isSuccess($deal)){
            $dayStat['COUNT_SINGLE'] += 1;
            $dayStat['SUM_SINGLE'] += $deal['OPPORTUNITY'];
            $dayStat['SUM_COMMON'] += $deal['OPPORTUNITY'];

            $mainStat['COUNT_SINGLE'] += 1;
            $mainStat['SUM_SINGLE'] += $deal['OPPORTUNITY'];
            $mainStat['SUM_COMMON'] += $deal['OPPORTUNITY'];
        }

        if(($this->isRegular($deal) || $this->isSingle($deal)) && $this->isAttempt($deal)){
            $dayStat['COUNT_ATTEMPT'] += 1;
            $mainStat['COUNT_ATTEMPT'] += 1;
        }
    }

    public function isInit($deal){
        return $deal['UF_CRM_1642597942'] == 'регулярное (Инитное)';
    }

    public function isRepeat($deal){
        return $deal['UF_CRM_1642597942'] == 'регулярное (Повторное)';
    }

    public function isRegular($deal){
        return $this->isInit($deal) || $this->isRepeat($deal);
    }

    public function isSingle($deal){
        return $deal['UF_CRM_1642597942'] == 'разовое';
    }

    public function isSuccess($deal){
        return $deal['UF_CRM_1642597927'] == 'Оплачено';
    }

    public function isAttempt($deal){
        return $deal['UF_CRM_1642597927'] == 'Попытка пожертвовать';
    }

    public function formatingDealList(){
        foreach($this->dealList as $deal){
            preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/', $deal['DATE_CREATE'], $matches);
            $dateCreate = $matches[0];
            unset($deal['DATE_CREATE']);
            $this->formatDealList[$dateCreate][] = $deal;   
        }    
    }

    public function getDealListAll(Request $request)
    {
        $this->initFilter($request);
        $response = $this->getDealList();
        
        $this->dealList = array_merge($this->dealList, $response['result']);
        
        $countQuery = intval($response['total'] / self::countElement);
        $countBatchQuery = intval($countQuery / self::countQueryInBatch);
        
        foreach(range(0, $countBatchQuery) as $i){
            $start = $i * self::countQueryInBatch + 1;
            $end = ($i + 1) * self::countQueryInBatch > $countQuery? $countQuery: ($i + 1) * self::countQueryInBatch;
            $response = $this->getDealListBatchQuery($start, $end);
            
            foreach($response['result']['result'] as $result){
                $this->dealList = array_merge($this->dealList, $result);
            }
        }    
    }

    public function getDealList(): Array
    {
        $response = $this->client->call(new RestMethod('crm.deal.list',  $this->params));
        
        return $this->encoder->decode($response->getBody()->__toString(), 'json');

    }

    public function getDealListBatchQuery($start, $end)
    {
        foreach(range($start, $end) as $i){
            $cmd[$i] = 'crm.deal.list?' . http_build_query(array_merge($this->params, ['start' => $i*self::countElement]));
        }        
        
        $response = $this->client->call(new RestMethod('batch',  [
            'halt' => 0, 
            'cmd' => $cmd
        ]));

        return $this->encoder->decode($response->getBody()->__toString(), 'json');
    }

    public function initFilter(Request $request)
    {
        $start = '';
        $end = '';

        if($request->get('start')){
            try{
                $start = (new \DateTime($request->get('start')))->format('Y-m-d');
            }catch (\Exception $e){
                $this->errors[] = 'Неверный формат даты начала';
            }
        }
        if($request->get('end')){
            try{
                $end = (new \DateTime($request->get('end')))->add(new \DateInterval("P1D"))->format('Y-m-d');
            }catch (\Exception $e){
                $this->errors[] = 'Неверный формат даты завержения';
            }
        }

        if($start > $end && !empty($start) && !empty($end)){
            $this->errors[] = 'Дата завершения меньше даты старта';
        }else{
            if(!empty($start)){
                $this->params['filter'] += ['>DATE_CREATE' => $start];
            }

            if(!empty($end)){
                $this->params['filter'] += ['<DATE_CREATE' => $end];
            }
        }

        $type = $request->get('type');
        if(!empty($type)){
            $this->params['filter'] += ['UF_CRM_1642591052' => $type];
        }
    }
}
