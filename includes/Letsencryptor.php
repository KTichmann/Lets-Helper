<?php
    class Letsencryptor {
        private $wpdb;
        function __construct(){
            //Make wpdb object accessible throughout the class
            global $wpdb;
            $this->wpdb = $wpdb;
            //call init functions
        }
        private function init(){
            //initialize on-page info - called in render method

            //populate table if empty
            $certificates = $this->get_certificates_from_database();
            if(!$certificates){
                $this->get_certificates_from_server();
                $certificates = $this->get_certificates_from_database();
            }
            //prepare and display popup modals
            $this->create_modal_display();
            $this->renew_modal_display();
            $this->change_user_modal_display();
            $this->revoke_modal_display();
            $this->refresh_list();
            //adds ability to force reading from server using read_from_server=true param in url
            $this->force_read_from_server();

            //enqueues thickbox js & css for modal display
            add_thickbox();

            return $certificates;
        }

        public function render(){
            $certificates = $this->init();
            ?>
                <div id="lh_letsencrypt_main" class="container">
                    <h1 class="text-center">SSL Certificates</h1>
                    <hr>
                    <div class="row bo-pad-top text-center">
                        <div class="col-sm-4">
                            <a href="<?php echo esc_url( add_query_arg( 'list', 'true' ) ) ?>" >
                            <i class="fa fa-list-ul fa-5x"></i>
                            <h4>Refresh List</h4>
                            </a>
                        </div>
                        <div class="col-sm-4">
                            <a href="#TB_inline?&width=600&height=350&inlineId=create-modal" class="thickbox">
                                <i class="fa fa-plus-square fa-5x"></i>
                                <h4>Add Certificate</h4>
                            </a>
                        </div>
                        <div class="col-sm-4">
                            <a href="#TB_inline?&width=600&height=350&inlineId=change-user-modal" class="thickbox" >
                                <i class="fa fa-users fa-5x"></i>
                            <h4>Change User</h4>
                            </a>
                        </div>
                    </div>
                </div>
                <div><?php
                    if(count($certificates) > 0): ?>
                        <table class="table bo-pad-top mt-5">
                            <thead class="text-center">
                                <th scope="col">Certificate Name</th>
                                <th scope="col">Domain Names</th>
                                <th scope="col">Expiry Date</th>
                                <th scope="col">Options</th>
                            </thead>
                            <tbody>
                                <?php foreach($certificates as $row): array_map('htmlentities', $row); ?>
                                <tr>
                                    <td scope="row"><?php echo implode('</td><td>', $row); ?></td>
                                    <td class="text-center"><a href="<?php echo esc_url( add_query_arg( array('domain_name' => $row['certname'], 'renew' => true))); ?>"  class="btn btn-primary mr-2">Renew</a><a href="<?php echo esc_url( add_query_arg( array('domain_name' => $row['certname'], 'revoke' => true))); ?>" class="btn btn-danger">Revoke</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <script>lh_letsencrypt_helper()</script>
            <?php
        }

        //
        //Modal Displays
        //

        private function create_modal_display(){
            $this->create_modal_logic();
            ?>
                <div id="create-modal" style="display:none;">
                    <div id= "create-modal-inner" class="container">
                        <h1 class="text-center">Create Certificate</h1>
                        <form id="add-domain" action="" class="form-group" method="POST">
                            <label for="domain">Domain Name</label>
                            <input class="form-control" type="text" name="domain"  id="domain">
                            <input type="checkbox" name="dry-run" id="dry-run" style="display:none;">
                            <input class="row btn btn-primary mt-3 ml-1" type="submit" value="Do a Dry Run" id="dry-run-submit" name="submit_dry">
                            <input class="row btn btn-success mt-3 ml-1" type="submit" value="submit" id="submit-btn" name="submit_btn">
                        </form>
                        <div id="response"></div>
                    </div>
                </div>
            <?php
        }

       

        private function renew_modal_display(){
            $this-> renew_modal_logic();
            ?>
                <div id="renew-modal" style="display:none;">
                    <div id= "renew-modal-inner" class="container">
                        <h2 class="text-center">Manual Certificate Renewal</h2>
                        <div id="renew_responses"></div>
                    </div>
                </div>
            <?php
        }

        private function revoke_modal_display(){
            $response = $this->revoke_modal_logic();
            ?>
            <div id="revoke-modal" style="display:none;">
                <div id= "revoke-modal-inner" class="container">
                    <h2 class="text-center">Revoke Certificate</h2>
                    <div class="text-center mt-3" id="revoke-responses"><p>Are you sure you want to remove certificate with domain: <?php echo $_GET["domain_name"]; ?></p></div>
                    <form id="revoke-domain" action="" class="form-group" method="POST">
                        <input display="none" name="revoke_domain" value="<?php echo $_GET["domain_name"]; ?>" readonly>
                        <input class="btn btn-danger mt-3 ml-1" type="submit" value="REVOKE CERTIFICATE" id="revoke-btn" name="revoke_btn">
                    </form>
                </div>
            </div>
            <?php
        }

        private function change_user_modal_display(){
            $this->change_user_modal_logic();
            ?>
                <div id="change-user-modal" style="display:none;">
                    <div id= "change-modal-inner" class="container">
                        <h1 class="text-center">Change User</h1>
                        <form id="change-email" action="" class="form-group" method="POST">
                            <label for="domain">Email Address</label>
                            <input class="form-control" type="text" name="change-email"  id="change-email">
                            <input class="row btn btn-primary mt-3 ml-1" type="submit" value="Submit" id="renew-btn" name="renew_btn">
                        </form>
                        <div id="change-user-response"></div>
                    </div>
                </div>
            <?php
        }

        //
        // Modal Logic
        //

        private function create_modal_logic(){
            //check if we're running a dry run
            if(isset($_POST["dry-run"])){
                $dry_run_response = $this->add_certificate_dry_run($_POST["domain"]);
                ?>
                <script>
                    jQuery(function($){
                        $(document).ready(function(){
                            //make the input bar readonly
                            document.getElementById('domain').readOnly = true;
                            //put the domain into the input box
                            document.getElementById('domain').value = "<?php echo $_POST["domain"] ?>";
                            //hide the dry run submit box
                            document.getElementById('dry-run-submit').style.display = "none";
                            //show the modal again
                            tb_show('', '#TB_inline?&width=600&height=550&inlineId=create-modal', '');

                            //If the dry run is not successful:
                            <?php if(!$this->check_for_substring_in_array($dry_run_response, "The dry run was successful")){
                                //hide the submit button
                                ?> document.getElementById('submit-btn').style.display = "none";
                                //display an error
                                document.getElementById('response').innerHTML += "There was an error with the dry run. Please see the response from the server below. <br><br><pre> <?php foreach($dry_run_response as $string){echo $string . '<br>';}; ?> </pre> End of Response"
                            <?php 
                                } else{ //if the dry run is successful
                                    ?> document.getElementById('response').innerHTML += "The Dry Run Was Successful! <br><br> See the response from the server below for more info: <br> <?php foreach($dry_run_response as $string){echo $string . '<br>';} ?> \n End of Response"
                                    <?php
                                }
                            ?>
                        })
                    })
                </script>
                <?php   
            } else if(isset($_POST["submit_btn"])){ //if we're running an actual submission after a dry run
                $response = $this->add_certificate_actual_run($_POST["domain"]);
                if($response[0]){ //success
                    $response_html = "<h2>Success!</h2><br><p>Your certificate has been added successfully.</p>";
                } else{ //error
                    $response_string = $this->create_response_string_from_arr($response[1]);
                    $response_html = "<h2>Error</h2><br><h4>Please review the error log below<h4><br><pre>" . $response_string . "</pre>";
                }
                ?> <script>
                jQuery(function($){
                    $(document).ready(function(){
                        tb_show('', '#TB_inline?&width=600&height=550&inlineId=create-modal', ''); //open the modal again
                        document.getElementById('create-modal-inner').innerHTML = "<?php echo $response_html; ?>" //display the response
                    })
                })
                </script> <?php
            } else { //if we're opening the modal for the first time
                ?><script>
                jQuery(function($){
                    $(document).ready(function(){ //basic error checking for empty input and www
                        document.getElementById('dry-run-submit').addEventListener('click', e => {
                            e.preventDefault();
                            let domain = document.getElementById('domain').value;
                            let response = document.getElementById('response');
                            if(domain == ""){
                                response.innerHTML = "Domain name cannot be empty";
                            } else if(domain.startsWith('www.')){
                                response.innerHTML = "Please input the naked domain (without the www)";
                            } else{
                                document.getElementById('dry-run').checked = true;
                                document.getElementById('add-domain').submit();
                            }
                        })
                    })
                })
            </script><?php
                ?> 
                    <script>
                        jQuery(function($){
                            $(document).ready(function(){
                                document.getElementById('submit-btn').style.display = 'none'; // Hide the submit button until the dry run is done
                            })
                        })
                    </script>
                <?php
            }
        }

        private function renew_modal_logic(){
            if(isset($_GET["renew"])){
            $domain = ($_GET["domain_name"]);
            $response = $this->add_certificate_dry_run($domain); //dry run
            if($this->check_for_substring_in_array($response, "The dry run was successful")){ //if success
                $response = $this->add_certificate_actual_run($domain); //actual run
                if( $this->check_for_substring_in_array($response, "Congratulations! Your certificate and chain have been saved at") ){
                    $this->get_certificates_from_server();
                    $this->get_certificates_from_database(); //refresh table
                    ?> <script>jQuery(function($){ $(document).ready(function(){
                        //display success response
                        document.getElementById("renew_responses").innerHTML = "Success! The Certificate has been renewed. <br>"
                    }) })</script> <?php
                }
            } else { //there was an error in the dry run
                    $response_string = $this->create_response_string_from_arr($response) 
                            ?>
                                <script>
                                    jQuery(function($){ $(document).ready(function(){
                                        document.getElementById("renew_responses").innerHTML = "<h4>There was an error renewing the certificate, please review the error response below:</h4> <br> '<?php echo $response_string; ?>'"
                                        })
                                    })
                                </script>
                        <?php
                    }
                ?>
                //show the modal box to display result
                    <script>
                        jQuery(function($){
                            $(document).ready(function(){
                                tb_show('', '#TB_inline?&width=600&height=auto&inlineId=renew-modal', '');
                            })
                        })
                    </script>
                <?php   
            }
        }

        private function revoke_modal_logic(){
        if(isset($_GET["revoke"]) && !isset($_POST["revoke_domain"])){ //first button press - display double-check screen
            ?>
                <script>
                        jQuery(function($){
                            $(document).ready(function(){
                                tb_show('', '#TB_inline?&width=600&height=auto&inlineId=revoke-modal', '');
                            })
                        })
                </script>
            <?php
        } else if(isset($_POST["revoke_domain"])){ //double-check button clicked - revoke cert
            $revoke_domain = $_POST["revoke_domain"];
            exec("sudo ./scripts/lh-letsencrypt.sh revokecert $revoke_domain 2>&1", $output);
            if($this->check_for_substring_in_array($output, "Congratulations!")){
                $this->get_certificates_from_server();
                $this->get_certificates_from_database();
                $response = "Certificate Successfully Revoked";
            } else{
                $error_response = $this->create_response_string_from_arr($output);
                $response = "There was an error revoking this certificate. Check below for details. <br><br>" . $error_response;
            }
            ?>
                <script>
                    jQuery(function($){
                        $(document).ready(function(){
                            tb_show('', '#TB_inline?&width=600&height=auto&inlineId=revoke-modal', '');
                            document.getElementById("revoke-domain").style.display = "none";
                            document.getElementById("revoke-responses").innerHTML = "<?php echo $response; ?>";
                        })
                    })
                </script>
            <?php
        }
    }

    private function change_user_modal_logic(){
        if(isset($_POST["change-email"])){
            ?>
                <script>
                    jQuery(function($){
                        $(document).ready(function(){
                            tb_show('', '#TB_inline?&width=600&height=auto&inlineId=change-user-modal', '');
                        })
                    })
                </script>
            <?php
            $email = $_POST['change-email'];
            //Change the email on the server
            exec("sudo ./scripts/lh-letsencrypt.sh changeemail $email 2>&1", $output);
            if($this->check_for_substring_in_array($output, "Your e-mail address was updated")){
                $html_response = "Username Changed Successfully!";
            } else{
                $format_response = $this->create_response_string_from_arr($output);
                $html_response = "Error changing username: <br>" . $format_response; 
            }
                ?>
                    <script>
                        jQuery(function($){
                            $(document).ready(function(){
                                document.getElementById("change-user-response").innerHTML = "<?php echo $html_response; ?>";
                            })
                        })
                    </script>
                <?php
        }
    }

    //
    // Helper Functions
    //

    private function refresh_list(){
        if(isset($_GET["list"])){
            $this->get_certificates_from_database();
        }
    }

    private function add_certificate_dry_run($domain){
        $naked_domain = $domain;
        $www_domain = 'www.' . $domain;
        exec("sudo ./scripts/lh-letsencrypt.sh createdry $naked_domain $www_domain 2>&1", $output);
        return $output;
    }
    private function add_certificate_actual_run($domain){
        $naked_domain = $domain;
        $www_domain = 'www.' . $domain;
        exec("sudo ./scripts/lh-letsencrypt.sh createactual $naked_domain $www_domain", $output);
        if(check_for_substring_in_array($output, "Congratulations! Your certificate and chain have been saved at")){
            exec("sudo ./scripts.lh-letsencrypt.sh updatevhosts $naked_domain $naked_domain[0]");
            $this->get_certificates_from_server();
            return array(true, $output);
        } else{
            return array(false, $output);
        }
    }
    private function get_certificates_from_database(){
        $results = $this->wpdb->get_results("SELECT certname, domains, expirydate FROM {$this->wpdb->prefix}letsencrypt_ssl_certificates", ARRAY_A);
        $formatted_results = $this->format_certificates($results);
        return $formatted_results;
    }
    private function format_certificates($cert_arr){
        foreach ($cert_arr as &$item){
            $date = new DateTime($item['expirydate']);
            $now = new DateTime();
            $timeDifference = $date->diff($now)->format("%a");
            $item['expirydate'] = $timeDifference . " days";
        }
        return $cert_arr;
    }
    private function get_certificates_from_server(){
        exec("sudo ./scripts/lh-letsencrypt.sh certificates 2>&1", $output);
        foreach ($output as $key => $line_item){
            if(strpos($line_item, "Certificate Name:") !== false){
                $certName = substr($line_item, strpos($line_item, "Certificate Name:") + 17);
                $certDomains = substr($output[$key + 1], strpos($output[$key + 1], ":") + 1);
                $certExpiryDate = substr($output[$key + 2], strpos($output[$key + 2], ":") + 1);
                $certExpiryDate = strtok($certExpiryDate, '(');
                $this->letsencrypt_add_to_db($certName, $certDomains, $certExpiryDate);
            }
        }
        return 1;
    }
    private function create_response_string_from_arr($arr){
        $response_string = '';
        foreach($arr as $string){
            $response_string .= $string . '<br>';
        }
        return $response_string;
    }
    private function force_read_from_server(){
        if(isset($_GET["read_from_server"])){
            $this->get_certificates_from_server();
        }
    }

    private function letsencrypt_add_to_db($certname, $domains, $date){
        if(!$certname || !$domains || !$date){
            return false;
        } else{
            $table = $this->wpdb->prefix . 'letsencrypt_ssl_certificates';
            $date = new DateTime(trim($date));
            $date_string = $date->format('Y-m-d H:i:s');
            $certname = trim($certname);
            $domains = trim($domains);
            $data = array('certname' => $certname, 'domains' => $domains, 'expirydate' => $date_string);
            $check_if_row_exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM " . $this->wpdb->prefix . "letsencrypt_ssl_certificates WHERE certname = %s", $certname
                )
            );
            if($check_if_row_exists > 0){
                return 1;
            }else {
                $this->wpdb->insert($table, $data);
                return 0;
            }
        }
    }

    private function check_for_substring_in_array($arr, $string){
        foreach($arr as $value){
            if(strpos($value, $string) !== false){
                return true;
            }
        } 
        return false;
    }
}
    
function letsencrypt_create_db_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'letsencrypt_ssl_certificates';
    //Check if the table exists in the database - if not, create it.
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name){
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            certname varchar(100) NOT NULL,
            domains varchar(250) NOT NULL,
            expirydate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta( $sql );
    }
}
    
function lh_letsencrypt_init(){
    $letsencryptor = new Letsencryptor();
    $letsencryptor->render();
}