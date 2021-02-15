<template>
    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="form-group row">
                    <label for="event_id" class="col-md-3 control-label text-right">Enter ID</label>
                    <div class="col-md-5">
                        <input type="text" name="event_id" v-model="event_id" class="form-control" id="event_id" placeholder="Event ID">
                    </div>
                    <button class="btn btn-success col-md-4" @click="findById()">
                        Find by ID
                    </button>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group row">
                    <label for="user_id" class="col-md-3 control-label text-right">User ID</label>
                    <div class="col-md-5">
                        <input type="text" name="user_id" v-model="user_id" class="form-control" id="user_id" placeholder="User ID">
                    </div>
                    <button class="btn btn-success col-md-4" @click="findByUser()">
                        Find by User
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="form-group row">
                    <label for="case_id" class="col-md-3 control-label text-right">Case ID</label>
                    <div class="col-md-5">
                        <input type="text" name="case_id" v-model="case_id" class="form-control" id="case_id" placeholder="Case ID">
                    </div>
                    <button class="btn btn-success col-md-4" @click="findByCase()">
                        Find by Case
                    </button>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group row">
                    <label for="company_id" class="col-md-4 control-label text-right">Company ID</label>
                    <div class="col-md-5">
                        <input type="text" name="company_id" v-model="company_id" class="form-control" id="company_id" placeholder="Company ID">
                    </div>
                    <button class="btn btn-success col-md-3" @click="findByCompanyID()">
                        Find
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    <label for="case_id_for_all" class="col-md-2 control-label text-right">Case ID</label>
                    <div class="col-md-4">
                        <input type="text" name="case_id_for_all" v-model="case_id_for_all" class="form-control" id="case_id_for_all" placeholder="Case ID">
                    </div>
                    <button class="btn btn-success col-md-6" @click="findAllByCase()">
                        find All By Case
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    <label for="" class="col-md-2 control-label text-left">TEST API</label>
                    <button class="btn btn-danger col-md-10" @click="testAPI()">
                        TEST API
                    </button>
                </div>
            </div>
        </div>
        <hr>
        <!-- query result -->
        <div class="row response">
            <div class="col-12">
                <label class="d-block">Response</label>
                <div class="message">
                  <p>{{ message }}</p>
                </div>
                <label class="d-block">Query</label>
                <div class="query">
                  <p>{{ query }}</p>
                </div>
                <label class="d-block">Result</label>
                <div class="result">
                  <p>{{ result }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
      data() {
          return {
              message: "",
              query: "",
              result: "",
              
              event_id: "",
              user_id: "",
              case_id: "",
              company_id: "",
              case_id_for_all: "",
          };
      },
      mounted() {
          console.log("Component mounted.");
          //this.getRecentWords();
      },
      methods: {
          findById() {
              if (this.event_id) {
                  axios
                      .get("/api/find-by-id", {
                        params : { event_id: this.event_id }
                      })
                      .then(response => {
                          console.log(response.data);
                          let res = response.data;
                          if (res.status == "success") {
                              this.query = res.query;
                              this.result = res.data;
                          }
                      });
              }
          },
          findByUser() {
              if (this.user_id) {
                  axios
                      .get("/api/find-by-user", {
                        params : {
                          user_id: this.user_id,
                          offset: 0,
                          limit: 50
                        }
                      })
                      .then(response => {
                          console.log(response.data);
                          let res = response.data;
                          if (res.status == "success") {
                              this.query = res.query;
                              this.result = res.data;
                          }
                      });
              }
          },
          findByCase() {
              if (this.case_id) {
                  axios
                      .get("/api/find-by-case", {
                        params : {
                          case_id: this.case_id
                        }
                      })
                      .then(response => {
                          console.log(response.data);
                          let res = response.data;
                          if (res.status == "success") {
                              this.query = res.query;
                              this.result = res.data;
                          }
                      });
              }
          },
          findByCompanyID() {
              if (this.company_id) {
                  axios
                      .get("/api/find-by-company", {
                        params : {
                          company_id: this.company_id
                        }
                      })
                      .then(response => {
                          console.log(response.data);
                          let res = response.data;
                          if (res.status == "success") {
                              this.query = res.query;
                              this.result = res.data;
                          }
                      });
              }
          },
          findAllByCase() {
              if (this.case_id_for_all) {
                  axios
                      .get("/api/find-all-case", {
                        params : {
                          case_id: this.case_id_for_all
                        }
                      })
                      .then(response => {
                          console.log(response.data);
                          let res = response.data;
                          if (res.status == "success") {
                              this.message = res.message;
                              this.query = res.query;
                              this.result = res.data;
                          }
                      });
              }
          },
          
          
          testAPI() {
              axios
                .get("/api/test-api")
                .then(response => {
                    console.log(response.data);
                    let res = response.data;
                    if (res.status == "success") {
                        this.message = res.message;
                        this.query = res.query;
                        this.result = res.data;
                    }
                });
          },
      }
  };
</script>
<style scoped>
  .query, .result {
    width: 100%;
    min-height: 100px;
    padding: 10px;
    color: black;
    background: white;
    border-radius: 4px;
    overflow-y: scroll;
  }
  .query {
    margin-bottom: 20px;
  }
</style>
