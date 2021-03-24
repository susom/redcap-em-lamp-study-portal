<?php


namespace Stanford\LampStudyPortal;
use PHPUnit\Exception;


class AlternateAdjudication
{
    public function __construct($client)
    {
        $this->setClient($client);
    }

    public function updateTaskSimple($user_uuid, $task_uuid, $results, $confidence,
                                     $readable, $comments, $passfail, $reagents, $volume, $disc, $seal)
    {
        try {
            $numargs = func_num_args();

            if($numargs === 11) {
                $data['task_uuid'] = $task_uuid;
                $data['status'] = 'completed';
                $data['coordinator_response'] = $results;
                $data['coordinator_user_id'] = USERID;
                $data['readable'] = $readable;

                $data['adjudication_date'] = gmdate("Y-m-d\TH:i:s\Z");
                $data['adj_conf'] = $confidence;
                $data['comments'] = $comments;
                $data['passfail'] = $passfail;
                $data['reagents'] = $reagents;
                $data['volume'] = $volume;
                $data['disc'] = $disc;
                $data['seal'] = $seal;

                $save = \REDCap::saveData(
                    $this->getClient()->getEm()->getProjectId(),
                    'json',
                    json_encode(array($data))
                );

                if (!empty($save['errors'])) {
                    if (is_array($save['errors']))
                        throw new \Exception('Error: '. implode(".", $save['errors']));
                    else
                        throw new \Exception('Error: '. $save['errors']);
                }

                http_response_code(200);//return 200 on success
            }else{
                throw new \Exception('ERROR: encountered erroneous response data for user: ' . $user_uuid . ' task ' . $record_data->provider_task_uuid);
            }

        } catch (\Exception $e) {
            $this->getClient()->getEm()->emError($e->getMessage());
            http_response_code(400);
        }
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
