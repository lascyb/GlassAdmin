                    <el-form-item label="<%label%>">
                        <el-date-picker
                            v-model="advanced.<%prop%>"
                            type="datetimerange"
                            value-format="<%valueFormat%>"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            class="!w-72"
                        />
                    </el-form-item>
