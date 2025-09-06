const template = `
<div class="q-mt-lg">
    <div v-if="!isLoading">
        

        <div class="row q-col-gutter-md">
            <div class="col-6">
                
                <p>Cài đặt tài khoản</p>
                <q-input  filled v-model="settings.account.name"  label="Chủ tài khoản" class="q-mb-md"/>
                <q-input filled v-model="settings.account.phone" label="Số tài khoản" class="q-mb-md"/>

                

                <p><q-toggle  v-model="settings.telegram"  label="Thông báo Telegram" /></p>
                <q-input  filled v-model="settings.telegram_token"  label="Token Telegram" class="q-mb-md"/>
                <q-input  filled v-model="settings.telegram_group"  label="Group Telegram" class="q-mb-md"/>
                <q-input  filled v-model="settings.lark_hook"  label="Thông báo Lark" class="q-mb-md"/>
                <p><a href="https://thietkewebgiarehcm.com/huong-dan-tao-bot-va-gui-thong-bao-qua-telegram/">Hướng dẫn lấy Token Telegram</a></p>
                <br>
                <q-input filled v-model="settings.notify.order_completed" label="Thông báo sau khi mua hàng thành công" class="q-mb-md"/>
           <!--      <p><q-toggle  v-model="settings.pusher.active"  label="Thông báo Pusher" /></p>
                <q-input  filled v-model="settings.pusher.app_id"  label="APP ID" class="q-mb-md"/>
                <q-input filled v-model="settings.pusher.key" label="Key" class="q-mb-md"/>
                <q-input filled v-model="settings.pusher.secret" label="Secret" class="q-mb-md"/>
                <q-input filled v-model="settings.pusher.cluster" label="Cluster" class="q-mb-md"/> -->
                

                <q-input  filled v-model="settings.api_server"  label="API Server" class="q-mb-md"/>

                <q-btn color="primary"  icon="save" class="q-mb-lg q-mt-lg" label="Lưu" @click="save"/>
                <q-btn color="pink"  icon="delete" class="q-mb-lg q-mt-lg" label="Xóa dữ liệu" @click="resetAllData"/>
                
                
            </div>  
            <div class="col-6">
                <p>Phương thức thanh toán</p>
                <q-input  filled v-model="settings.notify.payment_gateway_label"  label="Tên phương thức thanh toán" class="q-mb-md"/>
                <q-input  filled v-model="settings.notify.method_description"  label="Mô tả phương thức" class="q-mb-md"/>
                <p>Lời nhắn trong giao dịch(Ví dụ: DH176) trong đó DH là tiền tố</p>
                <q-input  filled v-model="settings.prefix"  label="Tiền tố" class="q-mb-md"/>
                <q-input  filled v-model="settings.subfix"  label="Hậu tố" class="q-mb-md"/>
                <p>Quy đổi ngoại tệ ra VNĐ (Nếu website bạn sử dụng khác VNĐ thì nhập tỉ giá quy đổi)</p>
                <q-input filled v-model="settings.currency_rate" label="Tỷ giá" class="q-mb-md"/>
                <p>Chuyển tới trang cảm ơn</p>
                <q-input filled v-model="settings.url_redirect" label="Link chuyển hướng sau khi thanh toán thành công (nếu có)" class="q-mb-md"/>
                 <p>Trạng thái đơn hàng sau khi tạo đơn</p>
                <q-select
                        filled
                        v-model="settings.create_order_status"
                        :options="orderStatusOptions"
                        label="Trạng thái"
                        emit-value
                        map-options
                      />
                <p>Trạng thái đơn hàng sau khi thanh toán thành công</p>
                <q-select
                        filled
                        v-model="settings.order_status"
                        :options="orderStatusOptions"
                        label="Trạng thái"
                        emit-value
                        map-options
                      />
                
                <p class="q-mt-md"><q-toggle  v-model="settings.reload_after_completed"  label="Tự động tải lại trang sau khi thanh toán thành công" /></p>

                
            </div>  

        </div>
    </div>
</div>   
`;

const { RV_CONFIGS } = window 
const orderStatusOptions = [
        {label: 'Chờ thanh toán', value: 'pending'},
        {label: 'Đang xử lý', value: 'processing'},
        {label: 'Tạm giữ', value: 'on-hold'},
        {label: 'Đã hoàn thành', value: 'completed'}
];
export default {
    data: () => ({

        isLoading: false,
        settings: {
            telegram: false,
            telegram_token: '',
            telegram_group: '',
            lark_hook: '',
            prefix: 'DH',
            subfix: '',
            currency_rate: '',
            qr: '',

            notify: {
                method_description: 'Thanh toán online bằng Vietcombank ngay tức thì',
                order_completed: 'Thanh toán thành công - Đơn hàng hoàn tất. Chúng tối sẽ sớm liên lạc lại cho bạn. ',
                payment_gateway_label: 'Thanh toán Vietcombank',
            },
            order_status: 'completed',
            create_order_status: 'pending',
            account:{
                phone: '',
                name: '',
            },
            pusher: {
                active: true,
                app_id: '',
                key: '',
                secret: '',
                cluster: '',
            },
            ssl_fix_too_small: false,
            api_server: '',
            url_redirect: '',
            reload_after_completed: true
            
        },
        orderStatusOptions
            
    }),
   
    methods: {
        uploadQR(){
            const file_frame = wp.media.frames.file_frame = wp.media({ title: 'Upload ảnh mã QR', library: { type: 'image' }, button: { text: 'Lựa chọn' }, multiple: false });

            file_frame.on('select',  () => {

                const attachment = file_frame.state().get('selection').first().toJSON();
                this.settings.qr = attachment.url
                
            });
            file_frame.open();
        },
        save() {
            this.$q.loading.show()
            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({action: 'vcb_gw_save_option',key: 'vcb_gw_settings', data: this.settings})).then(res => {
                const {success, msg} = res.data
              
                this.NOTIFY(msg, success);
                this.$q.loading.hide()
                if(success)
                    window.vcb_gw_settings = Object.assign({}, this.settings)
            })
        },
       
        coppyShortcode(){
            Quasar.utils.copyToClipboard('[wp_reviews_mh]')
                // this.NOTIFY('Coppy short_code thành công');

        },
        resetAllData(){
            this.$q.dialog({
                dark: true,
                title: 'Xóa toàn bộ dữ liệu',
                message: 'Bao gồm dữ liệu giao dịch, thông tin tài khoản. Gõ vcb-gateway-mh',
                prompt: {
                model: '',
                type: 'text' // optional
                },
                cancel: true,
                persistent: true
            }).onOk(data => {
               if(data == 'vcb-gateway-mh')
               {
                   axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_reset_all_data' })).then(res => {
                       const {success, msg} = res.data
                       this.NOTIFY(msg, success);
                   })
               }
            })
        }
    },
    components:{
        
    },
    template: template,
    created(){
     
        this.getSettings().then(data => {
            this.settings = this.deepMerge(this.settings, data);
        })
        this.$eventBus.$emit('set.page_title', 'Cài đặt');
       
    },
    destroyed(){
    }

}