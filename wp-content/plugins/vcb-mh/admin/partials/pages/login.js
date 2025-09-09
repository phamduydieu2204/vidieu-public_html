

const template = `
<div class="q-mt-lg">
    <div v-if="!isLoading">
        
        <p>
         
        </p>
        <q-stepper
             v-model="step"
             header-nav
             ref="stepper"
             color="primary"
             animated
           >
             <q-step
               :name="1"
               title="Nhập thông tin tài khoản"
               icon="settings"
               :done="done1"
             >
               <div class="row q-col-gutter-md">
                   <div class="col-4">
                       <q-input outlined v-model="auth.phone" label="Nhập số điện thoại" class="q-mb-md" autocomplete="off"/>
                       <q-input type="password" outlined v-model="auth.password" label="Nhập mật khẩu" autocomplete="off"/>
                   </div>
                  
               </div>

               <q-stepper-navigation>
                 <q-btn @click="step1" color="primary" label="Tiếp tục" />
               </q-stepper-navigation>
             </q-step>

             <q-step
               :name="2"
               title="Nhập mã OTP"
               icon="create_new_folder"
               :done="done2"
             >
               <div class="col-4">
                       <q-input outlined v-model="auth.otp" label="Nhập mã OTP" class="q-mb-md" autocomplete="off"/>
               </div>

               <q-stepper-navigation>
                 <q-btn @click="step2" color="primary" label="Tiếp tục" />
                 <q-btn flat @click="step = 1" color="primary" label="Quay lại" class="q-ml-sm" />
               </q-stepper-navigation>
             </q-step>

             <q-step
               :name="3"
               title="Hoàn tất đăng nhập"
               icon="add_comment"
               :done="done3"
             >
                 <div class="col-4">
                   <div>Đăng nhập thành công.</div>
                   <br>

                   <q-list bordered separator>
                         <q-item clickable v-ripple v-for="t in transactions">
                           <q-item-section>
                              <q-item-label caption>{{t.CD}} {{ t.Amount }}</q-item-label>
                              <q-item-label>{{t.Description}}</q-item-label>
                           </q-item-section>
                         </q-item>

                        
                   </q-list>
                </div>
              
             </q-step>
           </q-stepper>
       
    </div>
</div>   
`;

const { RV_CONFIGS } = window 
export default {
    data: () => ({
    	isLoading: false,
        configs: RV_CONFIGS,
        step: 1,
        done1: false,
        done2: false,
        done3: false,
        auth: {
            phone: '',
            password: '',
            imei: "48473d58-dc83-28a3-d568-5683e5645901",
            ohash: "c6c0c68c3b62d12284c0732ed18fc7a96d9757e550bbc66db08b8d6d872c4692",
            onesignal: "5f7063cf-cb29-e260-5048-0481127b25a7",
            otp: "",
            rkey: "ibnxKjPfQISwAkRdhyhJ",
            setupkey: "WgmFhTTpj9Aayvums5k6kQi3Kt3xJ99wp8rebgj0cJ5FXz5ZFHJGvnIcbokyyL4+",
            auth_token: '',
            ENCRYPT_KEY: '',
            // rkey: '',
            // imei: '',
            // onesignal: '',
            // otp: '',
            // ohash: '',
            // setupkey: '',
            // auth_token: '',
            // ENCRYPT_KEY: '',
        },
        transactions: [
        
        ]
    }),
   
    methods: {
    	step1(){
            
            if(this.auth.phone == '' || this.auth.password == '')
            {
                this.NOTIFY('Bạn cần điền đầy đủ thông tin', false)
                    return;
            }
            this.$q.loading.show();

            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_login', step: 1, auth: this.auth })).then(res => { 
                if(res.data.hasOwnProperty('userInfo')){
                    this.done2 = true
                    this.step = 3
                    this.step3()
                    this.$q.loading.hide();
                    return;

                }
                const { code } = res.data
                if(code == '00')
                {

                    this.done2 = true
                    this.step = 2
                   
                    this.NOTIFY('Vui lòng nhập OTP', true)

                }
                else
                    this.NOTIFY('Đăng nhập thất bại, hãy thử lại', false)

                  this.$q.loading.hide();
            })
        },
        step2(){
          

            if(this.auth.otp == '')
            {
                this.NOTIFY('Yêu cầu điền mã OTP', false)
                    return;
            }
            this.$q.loading.show();

            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_login', step: 2, auth: this.auth })).then(res => { 
                this.$q.loading.hide();

                const { code } = res.data
                if(code == '00')
                {
                    this.done2 = true
                    this.step = 3

                    this.step3()

                }
                else
                    this.NOTIFY('Đăng nhập thất bại, hãy thử lại', false)
            })
        },
        step3(){
            this.saveLogin();
            this.NOTIFY('Đăng nhập thành công', true)
        },
        saveLogin(){
            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_save_option', key: 'vcb_gw_login',  data: this.auth })).then(res => {
                console.log(res)
            })
        },
        saveProfile(){
            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_save_option', key: 'vcb_gw_profile',  data: this.profile })).then(res => {
                console.log(res)
            })
        }
	},
	components:{

	},
    template: template,
    created(){
        // this.step3()       
        this.$eventBus.$emit('set.page_title', 'Đăng nhập tài khoản');

    }

}