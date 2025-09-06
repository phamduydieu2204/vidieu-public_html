

const template = `
<div class="q-mt-lg">
    <div v-if="!isLoading">
            <div class="text-right">
              <q-chip color="purple" text-color="white" icon="directions" class="q-mb-md" v-if="profile.name">
                      Họ tên: {{profile.name}}
              </q-chip>
              <q-chip color="purple" text-color="white" icon="directions" class="q-mb-md" v-if="profile.originalPhone">
                      Tài khoản: {{profile.originalPhone}}
              </q-chip>
              <q-chip color="orange" text-color="white" icon="directions" class="q-mb-md" v-if="profile.balance">
                      Số dư: {{addCommas(profile.balance)}} đ
              </q-chip>
              <q-chip color="green" text-color="white" icon="refresh" class="q-mb-md" @click="getTransactions" clickable>
              Làm mới
              </q-chip>
            </div>
            
            <empty-component v-if="records.length == 0 && isLoading == false"/>
            <template v-else>
            

            <q-markup-table flat bordered>
                <thead>
                    <tr>
                        <th class="text-center" width="50px">ID</th>
                        <th class="text-left">Số tiền</th>
                        <th class="text-left">Nội dung</th>
                        <th class="text-left">Đơn hàng</th>
                        <th class="text-left">Trạng thái</th>
                        <th class="text-left">Thời gian</th>
                        <th class="text-left">IP</th>
                        <th class="text-left">#</th>
                        
                    </tr>
                </thead>
                <tbody>
                        
                    <tr v-for="(record, index) in records" :key="record.id">
                        <td>{{ record.id }}</td>
                        <td>+ {{ addCommas(record.amount) }} đ</td>
                        <td>{{ record.comment }}</td>
                        <td>{{ record.order_id }} <q-btn v-if="record.order_id" flat round color="primary" icon="link" @click="openURL(configs.site_url + '/wp-admin/post.php?post='+record.order_id+'&action=edit')"></td>
                        <td class="cursor-pointer">
                            <q-tooltip> {{record.description}} </q-tooltip>
                          <q-badge  :color="record.status == 999 ? 'green' : 'orange' " :label="record.status == 999 ? 'Thành công': 'Thất bại'">
                          </q-badge>
                        </td>
                        <td>{{ record.description }}</td>
                        <td>{{ record.ipAddress }}</td>
                        <td>
                          <q-btn round color="pink" icon="delete" size="sm"/>
                        </td>
                    </tr>
                    

                    
                   
                </tbody>
            </q-markup-table>
            <div class="flex flex-center q-mt-lg">
                Số trang
                <q-pagination v-model="pagination.page"  max-pages="6" :max="pagination.max" direction-links boundary-links icon-first="skip_previous" icon-last="skip_next" icon-prev="fast_rewind" icon-next="fast_forward" :disabled="isLoading"></q-pagination>

                | Tổng {{pagination.total}} | Số bản ghi trên trang
                <q-btn-dropdown color="primary" :label="pagination.per_page" class="q-ml-xs">
                    <q-list>

                        <q-item clickable v-close-popup>
                            <q-item-section @click="pagination.per_page = 10">
                                <q-item-label>10</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item clickable v-close-popup>
                            <q-item-section @click="pagination.per_page = 20">
                                <q-item-label>20</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item clickable v-close-popup>
                            <q-item-section @click="pagination.per_page = 50">
                                <q-item-label>50</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item clickable v-close-popup>
                            <q-item-section @click="pagination.per_page = 100">
                                <q-item-label>100</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup>
                            <q-item-section @click="pagination.per_page = 1000">
                                <q-item-label>1000</q-item-label>
                            </q-item-section>
                        </q-item>

                    </q-list>
                </q-btn-dropdown>
            </div>
        </div>
        </template>

        <div class="loading-m" v-else>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; display: block;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                <path
                    fill="none"
                    stroke="#e90c59"
                    stroke-width="7"
                    stroke-dasharray="42.76482137044271 42.76482137044271"
                    d="M24.3 30C11.4 30 5 43.3 5 50s6.4 20 19.3 20c19.3 0 32.1-40 51.4-40 C88.6 30 95 43.3 95 50s-6.4 20-19.3 20C56.4 70 43.6 30 24.3 30z"
                    stroke-linecap="round"
                    style="transform: scale(0.5700000000000001); transform-origin: 50px 50px;"
                >
                    <animate attributeName="stroke-dashoffset" repeatCount="indefinite" dur="1s" keyTimes="0;1" values="0;256.58892822265625"></animate>
                </path>
            </svg>
        </div>
</div>   
`;

const { RV_CONFIGS } = window 
export default {
    data: () => ({
        records: [],
        configs: RV_CONFIGS,
        filters: {
            search: '',
            post_type: '',
            post_id: '',
            status: 1,
            date_range: '',
            date: ''
        },
        profile:{
            balance: 0,
            originalPhone: '',
            name: ''
        },
        isLoading: true,
        pagination: {
          page: 1,
          max: 1,
          per_page: 20,
          total: 0,
        },
      
    }),
   
    methods: {

    formatDateRange(date){
        const replaceW = (d) => {
            return d.split('/').join('-');
        }

        if(date == '')
            return '';
        if(typeof date == 'object')
            return {from: replaceW(date.from), to: replaceW(date.to)}
        else
            return {from: replaceW(date), to: replaceW(date)}
    },
    updateProxy () {
            proxyDate.value = date.value
          },

          save () {
            date.value = proxyDate.value
          },
        formatDate(date){
            if(date == '' || date == null)
                return ;
            const n = date.split('-');
            return `${n[1]}/${n[2]}/${n[0]}`
        },
        remove(id){
          this.$q.dialog({
                title: 'Xác nhận',
                message: 'Bạn chắc chắn chứ',
                cancel: true,
                persistent: true
            }).onOk(() => {

            axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'tk_mh_carrier_delete', id, table: 'a_0' })).then(res => { 
              
             const {success, msg} = res.data
             this.NOTIFY(msg, success);
             if(success)
                this.getData()
            })

            })
        },
      getProfile(){
         axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_get_option', key: 'vcb_gw_profile' })).then(res => {
            console.log(res)
            if(res.data.data != null)
              this.profile = res.data.data
            console.log(res.data.data)
         })
      },
    	getData(e){
           this.isLoading = true
           if(this.filters.date_range)
           {
            this.filters.date = this.formatDateRange(this.filters.date_range);
           }
           axios.post(RV_CONFIGS.ajax_url, this.jsonToFormData({ action: 'vcb_gw_get_transactions', page: this.pagination.page, per_page: this.pagination.per_page, filters: this.filters })).then(res => { 
             
             this.records = res.data.data
             
             this.pagination.total = res.data.pagination.total
             this.pagination.max = res.data.pagination.max_page
             this.isLoading = false
           })
        },
      getTransactions(){
        this.NOTIFY('Vui lòng chờ trong giây lát...')
        this.$q.loading.show()
        axios(`${RV_CONFIGS.ajax_url}?action=vcb_gw_waiting_payment&cron=true&order_id=9000009`).then(res => { 
            this.getData();
            this.$q.loading.hide()
        })
      }
	},
	components:{

	},
    watch:{
      'pagination.page': function(){
          this.getData()
      },
      'filters.status': function(){
        this.getData()
      },
      'filters.search': function(){
        this.pagination.page = 1
        this.getData()
      },
      'filters.date_range': function(){
        this.pagination.page = 1
        this.getData()
      },
      'pagination.per_page': function(){
        this.pagination.page = 1
        this.getData()
      },
      
    },
    template: template,
    created(){
        this.getData()
        this.getProfile()
        this.$eventBus.$emit('set.page_title', 'Lịch sử giao dịch Vietcombank');

    }

}