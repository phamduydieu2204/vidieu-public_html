const template = `

    <div class="flex flex-center" style="padding-top:100px; flex-direction:column">
    <img
      alt="Quasar logo"
      :src=" configs.site_url + '/wp-content/plugins/vcb-mh/assets/images/box_empty.svg'"
      style="width: 200px; height: 200px"
    >
    <div class="q-mt-md">Dữ liệu rỗng</div>
    </div>   
`;



const { RV_CONFIGS } = window 
export default {
    data: () => ({
        configs: RV_CONFIGS,
    }),
   
    methods: {
    	
	},
	components:{

	},
    template: template,
    created(){
      
    }

}